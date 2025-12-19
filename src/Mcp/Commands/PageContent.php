<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Commands;

use Bnomei\KirbyMcp\Mcp\Support\PageResolver;
use Bnomei\KirbyMcp\Mcp\Support\RuntimeCommand;
use Bnomei\KirbyMcp\Mcp\Support\FieldSchemaHelper;
use Kirby\CLI\CLI;
use Throwable;

final class PageContent extends RuntimeCommand
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
            'description' => 'Reads a Kirby page content (structured output for MCP; prefer the `kirby_read_page_content` tool or `kirby://page/content/{encodedIdOrUuid}` resource template).',
            'args' => [
                'id' => [
                    'description' => 'Page id or uuid. If omitted, uses the home page.',
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

        try {
            if (is_string($id) && $id !== '') {
                $page = PageResolver::resolve($kirby, $id);
            } else {
                $page = $kirby->site()->homePage();
            }

            if ($page === null) {
                throw new \RuntimeException('Page not found');
            }

            $lang = is_string($language) && $language !== '' ? $language : null;
            $content = $page->content($lang)->toArray();
            $fieldSchemas = FieldSchemaHelper::fromFieldDefinitions($page->blueprint()->fields(), true);

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
                    'message' => 'WARNING: This page contains ' . implode('/', $presentComplex)
                        . ' fields. Before updating, read: ' . implode(', ', $schemaRefs),
                    'schemaRefs' => $schemaRefs,
                    'fieldTypes' => $presentComplex,
                ];
            }

            $beforeUpdateRead = [];
            $beforeUpdateSeen = [];
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

            $payload = [
                'ok' => true,
                'page' => [
                    'id' => $page->id(),
                    'uuid' => (string)$page->uuid(),
                    'template' => $page->template()->name(),
                    'url' => $page->url(),
                ],
                'language' => $lang,
                'keys' => array_keys($content),
                'truncatedKeys' => $truncatedKeys,
                'content' => $content,
                'fieldSchemas' => $fieldSchemas,
                'warningBlock' => $warningBlock,
                'BEFORE_UPDATE_READ' => $beforeUpdateRead,
                'warning' => 'Do not edit Kirby content files directly unless explicitly asked. Prefer the Panel or a safe tool like kirby_update_page_content. For payload shapes, see kirby://field/{type}/update-schema.',
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
