<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Runtime;

use Kirby\CLI\CLI;
use Throwable;

final class McpPageUpdateCommand extends McpRuntimeCommand
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
            'description' => 'Updates a Kirby page content (structured output for MCP)',
            'args' => [
                'id' => [
                    'description' => 'Page id or uuid.',
                ],
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
                    'message' => 'Page id/uuid is required.',
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
            $page = McpPageResolver::resolve($kirby, trim($id));
            if ($page === null) {
                throw new \RuntimeException('Page not found');
            }

            $updatedKeys = array_keys($data);

            if ($confirm !== true) {
                self::emit($cli, [
                    'ok' => false,
                    'needsConfirm' => true,
                    'message' => 'Dry run: pass --confirm to write changes.',
                    'page' => [
                        'id' => $page->id(),
                        'uuid' => (string)$page->uuid(),
                        'template' => $page->template()->name(),
                        'url' => $page->url(),
                    ],
                    'language' => $lang,
                    'validate' => $validate,
                    'updatedKeys' => $updatedKeys,
                ]);
                return;
            }

            $updated = $kirby->impersonate('kirby', static function () use ($page, $data, $lang, $validate) {
                return $page->update($data, $lang, $validate);
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
                'page' => [
                    'id' => $updated->id(),
                    'uuid' => (string)$updated->uuid(),
                    'template' => $updated->template()->name(),
                    'url' => $updated->url(),
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
}
