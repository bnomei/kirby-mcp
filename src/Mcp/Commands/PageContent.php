<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Commands;

use Bnomei\KirbyMcp\Mcp\Support\PageResolver;
use Bnomei\KirbyMcp\Mcp\Support\RuntimeCommand;
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
                    'id' => $page->id(),
                    'uuid' => (string)$page->uuid(),
                    'template' => $page->template()->name(),
                    'url' => $page->url(),
                ],
                'language' => $lang,
                'keys' => array_keys($content),
                'truncatedKeys' => $truncatedKeys,
                'content' => $content,
                'warning' => 'Do not edit Kirby content files directly unless explicitly asked. Prefer the Panel or a safe tool like kirby_update_page_content.',
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
