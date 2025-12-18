<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Runtime;

use Kirby\CLI\CLI;
use Throwable;

final class McpRenderCommand extends McpRuntimeCommand
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
            'description' => 'Renders a Kirby page (structured output for MCP)',
            'args' => [
                'id' => [
                    'description' => 'Page id (e.g. blog/post). If omitted, renders the home page.',
                ],
                'type' => [
                    'prefix' => 't',
                    'longPrefix' => 'type',
                    'description' => 'Content type / representation (html, json, rss, ...). Default: html.',
                ],
                'max' => [
                    'longPrefix' => 'max',
                    'description' => 'Max chars for html output (0 disables truncation). Default: 20000.',
                ],
                'noCache' => [
                    'longPrefix' => 'no-cache',
                    'description' => 'Bypass page cache (renders with dummy data to prevent cache hits).',
                    'noValue' => true,
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
        $type = $cli->arg('type') ?: 'html';

        $max = $cli->arg('max');
        $maxChars = is_numeric($max) ? (int)$max : 20000;
        if ($maxChars < 0) {
            $maxChars = 0;
        }

        $noCache = $cli->arg('noCache') === true;

        try {
            if (is_string($id) && $id !== '') {
                $page = McpPageResolver::resolve($kirby, $id);
            } else {
                $page = $kirby->site()->homePage();
            }

            if ($page === null) {
                throw new \RuntimeException('Page not found');
            }

            $data = $noCache ? ['__kirby_mcp' => true] : [];
            $html = $page->render(data: $data, contentType: $type);

            $truncated = false;
            if ($maxChars > 0 && strlen($html) > $maxChars) {
                $html = substr($html, 0, $maxChars);
                $truncated = true;
            }

            $payload = [
                'ok' => true,
                'page' => [
                    'id' => $page->id(),
                    'uuid' => (string)$page->uuid(),
                    'template' => $page->template()->name(),
                    'url' => $page->url(),
                ],
                'contentType' => $type,
                'truncated' => $truncated,
                'html' => $html,
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
