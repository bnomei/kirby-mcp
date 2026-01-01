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

final class FileContent extends RuntimeCommand
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
            'description' => 'Reads a Kirby file content (structured output for MCP; prefer the `kirby_read_file_content` tool or `kirby://file/content/{encodedIdOrUuid}` resource template).',
            'args' => [
                'id' => [
                    'description' => 'File id or uuid (file://...).',
                ],
                'language' => [
                    'longPrefix' => 'language',
                    'description' => 'Language code (optional). Default: current.',
                ],
                'max' => [
                    'longPrefix' => 'max',
                    'description' => 'Max chars per field value (0 disables truncation). Default: 20000.',
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
        $language = $cli->arg('language');

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

        try {
            $file = FileResolver::resolve($kirby, trim($id));
            if ($file === null) {
                throw new \RuntimeException('File not found');
            }

            $lang = is_string($language) && $language !== '' ? $language : null;
            $content = $file->content($lang)->toArray();
            $fieldSchemas = FieldSchemaHelper::fromFieldDefinitions($file->blueprint()->fields(), true);

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

            $complexTypes = ['layout', 'blocks', 'structure'];
            $presentComplex = [];
            foreach ($complexTypes as $type) {
                foreach ($fieldSchemas as $schema) {
                    if (($schema['type'] ?? null) === $type) {
                        $presentComplex[] = $type;
                        break;
                    }
                }
            }

            $warningBlock = null;
            if ($presentComplex !== []) {
                $schemaRefs = array_map(
                    static fn (string $type): string => 'kirby://field/' . $type . '/update-schema',
                    $presentComplex,
                );
                $warningBlock = [
                    'message' => 'WARNING: This file contains ' . implode('/', $presentComplex)
                        . ' fields. Before updating, read: ' . implode(', ', $schemaRefs),
                    'schemaRefs' => $schemaRefs,
                    'fieldTypes' => $presentComplex,
                ];
            }

            $beforeUpdateRead = ['kirby://blueprint/file/update-schema'];
            $beforeUpdateSeen = array_fill_keys($beforeUpdateRead, true);
            $collectSchema = static function (array $schema) use (&$beforeUpdateRead, &$beforeUpdateSeen): void {
                $schemaRef = $schema['_schemaRef']['updateSchema'] ?? null;
                if (!is_string($schemaRef) || $schemaRef === '' || isset($beforeUpdateSeen[$schemaRef])) {
                    return;
                }

                $beforeUpdateRead[] = $schemaRef;
                $beforeUpdateSeen[$schemaRef] = true;
            };

            foreach ($fieldSchemas as $schema) {
                if (!is_array($schema)) {
                    continue;
                }

                $collectSchema($schema);

                $nested = $schema['_nestedBlockFields'] ?? null;
                if (!is_array($nested)) {
                    continue;
                }

                foreach ($nested as $blockFields) {
                    if (!is_array($blockFields)) {
                        continue;
                    }

                    foreach ($blockFields as $nestedSchema) {
                        if (!is_array($nestedSchema)) {
                            continue;
                        }

                        $collectSchema($nestedSchema);
                    }
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

            $payload = [
                'ok' => true,
                'file' => [
                    'id' => $file->id(),
                    'uuid' => (string)$file->uuid(),
                    'filename' => $file->filename(),
                    'template' => $file->template(),
                    'url' => $file->url(),
                    'parent' => $parentInfo,
                ],
                'language' => $lang,
                'keys' => array_keys($content),
                'truncatedKeys' => $truncatedKeys,
                'content' => $content,
                'fieldSchemas' => $fieldSchemas,
                'warningBlock' => $warningBlock,
                'BEFORE_UPDATE_READ' => $beforeUpdateRead,
                'warning' => 'Do not edit Kirby content files directly unless explicitly asked. Prefer the Panel or a safe tool like kirby_update_file_content. For payload shapes, see kirby://field/{type}/update-schema.',
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
