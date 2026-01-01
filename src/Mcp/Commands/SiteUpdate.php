<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Commands;

use Bnomei\KirbyMcp\Mcp\Support\RuntimeCommand;
use Bnomei\KirbyMcp\Mcp\Support\FieldSchemaHelper;
use Kirby\CLI\CLI;
use Throwable;

final class SiteUpdate extends RuntimeCommand
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
            'description' => 'Updates the Kirby site content (structured output for MCP)',
            'args' => [
                'data' => [
                    'longPrefix' => 'data',
                    'description' => 'JSON object with fields to update (e.g. {"title":"Hello"}).',
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

        $rawData = $cli->arg('data');
        $language = $cli->arg('language');
        $validate = $cli->arg('validate') === true;
        $confirm = $cli->arg('confirm') === true;

        $max = $cli->arg('max');
        $maxChars = is_numeric($max) ? (int)$max : 20000;
        if ($maxChars < 0) {
            $maxChars = 0;
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
            $site = $kirby->site();

            $updatedKeys = array_keys($data);
            $fieldSchemas = FieldSchemaHelper::fromFieldDefinitions($site->blueprint()->fields());
            $schemaCheckReminder = ['kirby://blueprint/site/update-schema'];
            foreach ($updatedKeys as $fieldKey) {
                $schema = $fieldSchemas[$fieldKey]['_schemaRef']['updateSchema'] ?? null;
                if (is_string($schema) && $schema !== '' && !in_array($schema, $schemaCheckReminder, true)) {
                    $schemaCheckReminder[] = $schema;
                }
            }

            if ($confirm !== true) {
                self::emit($cli, [
                    'ok' => false,
                    'needsConfirm' => true,
                    'message' => 'Dry run: pass --confirm to write changes.',
                    'site' => [
                        'title' => $site->title()->value(),
                        'url' => $site->url($lang),
                    ],
                    'language' => $lang,
                    'validate' => $validate,
                    'updatedKeys' => $updatedKeys,
                    'schemaCheckReminder' => $schemaCheckReminder,
                ]);
                return;
            }

            $updated = $kirby->impersonate('kirby', static function () use ($site, $data, $lang, $validate) {
                return $site->update($data, $lang, $validate);
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

            $payload = [
                'ok' => true,
                'site' => [
                    'title' => $updated->title()->value(),
                    'url' => $updated->url($lang),
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
