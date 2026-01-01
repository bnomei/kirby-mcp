<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Commands;

use Bnomei\KirbyMcp\Mcp\Support\FileResolver;
use Bnomei\KirbyMcp\Mcp\Support\RuntimeCommand;
use Bnomei\KirbyMcp\Mcp\Support\FieldSchemaHelper;
use Kirby\CLI\CLI;
use Kirby\Cms\Page;
use Kirby\Cms\Site;
use Kirby\Cms\User;
use Throwable;

final class FileUpdate extends RuntimeCommand
{
    /**
     * @return array{
     *   description: string,
     *   args: array<string, mixed>,
     *   command: callable(CLI): void
     * }
     */
    public static function definition(): array
    {
        return [
            'description' => 'Updates a Kirby file content (structured output for MCP)',
            'args' => [
                'id' => [
                    'description' => 'File id or uuid (file://...).',
                ],
                'data' => [
                    'longPrefix' => 'data',
                    'description' => 'JSON object with fields to update (e.g. {"alt":"Hello"}).',
                ],
                'language' => [
                    'longPrefix' => 'language',
                    'description' => 'Language code (optional). Default: current.',
                ],
                'validate' => [
                    'longPrefix' => 'validate',
                    'description' => 'Validate values against blueprint rules before saving.',
                    'noValue' => true,
                ],
                'confirm' => [
                    'longPrefix' => 'confirm',
                    'description' => 'Actually write changes to disk. Without this flag, the command only returns a preview.',
                    'noValue' => true,
                ],
                'max' => [
                    'longPrefix' => 'max',
                    'description' => 'Max chars per field value in returned content (0 disables truncation). Default: 20000.',
                ],
            ],
            'command' => [self::class, 'run'],
        ];
    }

    public static function run(CLI $cli): void
    {
        $kirby = self::kirbyOrEmitError($cli);
        if ($kirby === null) {
            return;
        }

        $id = $cli->arg('id');
        $rawData = $cli->arg('data');
        $language = $cli->arg('language');
        $validate = $cli->arg('validate') === true;
        $confirm = $cli->arg('confirm') === true;

        $max = $cli->arg('max');
        $maxChars = is_numeric($max) ? (int)$max : 20000;
        if ($maxChars < 0) {
            $maxChars = 0;
        }

        if (!is_string($id) || trim($id) === '') {
            self::emit($cli, [
                'ok' => false,
                'error' => [
                    'class' => 'InvalidArgumentException',
                    'message' => 'File id/uuid is required.',
                    'code' => 0,
                ],
            ]);
            return;
        }

        if (!is_string($rawData) || trim($rawData) === '') {
            self::emit($cli, [
                'ok' => false,
                'error' => [
                    'class' => 'InvalidArgumentException',
                    'message' => 'Missing --data JSON object.',
                    'code' => 0,
                ],
            ]);
            return;
        }

        try {
            $decoded = json_decode($rawData, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            self::emit($cli, [
                'ok' => false,
                'error' => [
                    'class' => $exception::class,
                    'message' => 'Invalid JSON for --data: ' . $exception->getMessage(),
                    'code' => $exception->getCode(),
                ],
            ]);
            return;
        }

        try {
            $decoded = self::normalizeDecodedData($decoded);
        } catch (\JsonException $exception) {
            self::emit($cli, [
                'ok' => false,
                'error' => [
                    'class' => $exception::class,
                    'message' => 'Invalid JSON for --data: ' . $exception->getMessage(),
                    'code' => $exception->getCode(),
                ],
            ]);
            return;
        }

        if (!is_array($decoded) || $decoded === [] || array_is_list($decoded)) {
            self::emit($cli, [
                'ok' => false,
                'error' => [
                    'class' => 'InvalidArgumentException',
                    'message' => '--data must be a JSON object with field keys.',
                    'code' => 0,
                ],
            ]);
            return;
        }

        /** @var array<string, mixed> $data */
        $data = $decoded;

        $lang = is_string($language) && trim($language) !== '' ? trim($language) : null;

        try {
            $file = FileResolver::resolve($kirby, trim($id));
            if ($file === null) {
                throw new \RuntimeException('File not found');
            }

            $updatedKeys = array_keys($data);
            $fieldSchemas = FieldSchemaHelper::fromFieldDefinitions($file->blueprint()->fields());
            $schemaCheckReminder = ['kirby://blueprint/file/update-schema'];
            foreach ($updatedKeys as $fieldKey) {
                $schema = $fieldSchemas[$fieldKey]['_schemaRef']['updateSchema'] ?? null;
                if (is_string($schema) && $schema !== '' && !in_array($schema, $schemaCheckReminder, true)) {
                    $schemaCheckReminder[] = $schema;
                }
            }

            $parent = $file->parent();
            $parentInfo = null;
            if ($parent instanceof Page) {
                $parentInfo = [
                    'type' => 'page',
                    'id' => $parent->id(),
                    'uuid' => (string)$parent->uuid(),
                    'url' => $parent->url(),
                ];
            } elseif ($parent instanceof Site) {
                $parentInfo = [
                    'type' => 'site',
                    'title' => $parent->title()->value(),
                    'url' => $parent->url(),
                ];
            } elseif ($parent instanceof User) {
                $parentInfo = [
                    'type' => 'user',
                    'id' => $parent->id(),
                    'email' => $parent->email(),
                ];
            }

            if ($confirm !== true) {
                self::emit($cli, [
                    'ok' => false,
                    'needsConfirm' => true,
                    'message' => 'Dry run: pass --confirm to write changes.',
                    'file' => [
                        'id' => $file->id(),
                        'uuid' => (string)$file->uuid(),
                        'filename' => $file->filename(),
                        'template' => $file->template(),
                        'url' => $file->url(),
                        'parent' => $parentInfo,
                    ],
                    'language' => $lang,
                    'validate' => $validate,
                    'updatedKeys' => $updatedKeys,
                    'schemaCheckReminder' => $schemaCheckReminder,
                ]);
                return;
            }

            $updated = $kirby->impersonate('kirby', static function () use ($file, $data, $lang, $validate) {
                return $file->update($data, $lang, $validate);
            });

            $content = $updated->content($lang)->toArray();

            $truncatedKeys = [];
            if ($maxChars > 0) {
                foreach ($content as $key => $value) {
                    if (!is_string($value) || strlen($value) <= $maxChars) {
                        continue;
                    }

                    $content[$key] = substr($value, 0, $maxChars);
                    $truncatedKeys[] = $key;
                }
            }

            $parent = $updated->parent();
            $updatedParentInfo = null;
            if ($parent instanceof Page) {
                $updatedParentInfo = [
                    'type' => 'page',
                    'id' => $parent->id(),
                    'uuid' => (string)$parent->uuid(),
                    'url' => $parent->url(),
                ];
            } elseif ($parent instanceof Site) {
                $updatedParentInfo = [
                    'type' => 'site',
                    'title' => $parent->title()->value(),
                    'url' => $parent->url(),
                ];
            } elseif ($parent instanceof User) {
                $updatedParentInfo = [
                    'type' => 'user',
                    'id' => $parent->id(),
                    'email' => $parent->email(),
                ];
            }

            $payload = [
                'ok' => true,
                'file' => [
                    'id' => $updated->id(),
                    'uuid' => (string)$updated->uuid(),
                    'filename' => $updated->filename(),
                    'template' => $updated->template(),
                    'url' => $updated->url(),
                    'parent' => $updatedParentInfo,
                ],
                'language' => $lang,
                'validate' => $validate,
                'updatedKeys' => $updatedKeys,
                'truncatedKeys' => $truncatedKeys,
                'content' => $content,
            ];
        } catch (Throwable $exception) {
            $payload = [
                'ok' => false,
                'error' => self::errorArray($exception, self::traceForCli($cli, $exception)),
            ];
        }

        self::emit($cli, $payload);
    }

    /**
     * @return mixed
     */
    private static function normalizeDecodedData(mixed $decoded): mixed
    {
        for ($depth = 0; $depth < 3; $depth++) {
            if (is_string($decoded) && trim($decoded) !== '') {
                $decoded = json_decode($decoded, true, flags: JSON_THROW_ON_ERROR);
                continue;
            }

            if (is_array($decoded) && array_is_list($decoded) && count($decoded) === 1) {
                $only = $decoded[0] ?? null;

                if (is_string($only) && trim($only) !== '') {
                    $decoded = json_decode($only, true, flags: JSON_THROW_ON_ERROR);
                    continue;
                }

                if (is_array($only) && $only !== [] && !array_is_list($only)) {
                    $decoded = $only;
                    continue;
                }
            }

            break;
        }

        return $decoded;
    }
}
