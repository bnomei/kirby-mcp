<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Commands;

use Bnomei\KirbyMcp\Mcp\Support\RuntimeCommand;
use Bnomei\KirbyMcp\Project\KirbyMcpConfig;
use Kirby\CLI\CLI;
use Throwable;

final class EvalPhp extends RuntimeCommand
{
    public const ENV_ENABLE_EVAL = 'KIRBY_MCP_ENABLE_EVAL';

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
            'description' => 'Executes arbitrary PHP code in Kirby context (DANGEROUS; disabled by default) and returns structured JSON for MCP.',
            'args' => [
                'code' => [
                    'description' => 'PHP code to execute (like `php -r`). Available variables: $kirby, $app, $site, $page, $cli. Tip: end with `return ...;` to capture a return value.',
                ],
                'confirm' => [
                    'longPrefix' => 'confirm',
                    'description' => 'Actually execute the code. Without this flag, the command only returns a dry-run response.',
                    'noValue' => true,
                ],
                'max' => [
                    'longPrefix' => 'max',
                    'description' => 'Max chars for captured stdout/return dump (0 disables truncation). Default: 20000.',
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

        $projectRoot = $cli->dir();

        if (self::isEvalEnabled($projectRoot) !== true) {
            self::emit($cli, [
                'ok' => false,
                'enabled' => false,
                'needsEnable' => true,
                'message' => 'Eval is disabled by default. Enable via env ' . self::ENV_ENABLE_EVAL . '=1 or via .kirby-mcp/mcp.json: {"eval":{"enabled":true}}.',
            ]);
            return;
        }

        $confirm = $cli->arg('confirm') === true;
        if ($confirm !== true) {
            self::emit($cli, [
                'ok' => false,
                'enabled' => true,
                'needsConfirm' => true,
                'message' => 'Dry run: pass --confirm to execute code.',
                'available' => [
                    'variables' => ['$kirby', '$app', '$site', '$page', '$cli'],
                    'note' => 'Use `return ...;` to capture a return value.',
                ],
            ]);
            return;
        }

        $code = $cli->arg('code');
        if (!is_string($code) || trim($code) === '') {
            self::emit($cli, [
                'ok' => false,
                'enabled' => true,
                'error' => [
                    'class' => 'InvalidArgumentException',
                    'message' => 'Missing eval code argument.',
                    'code' => 0,
                ],
            ]);
            return;
        }

        $max = $cli->arg('max');
        $maxChars = is_numeric($max) ? (int)$max : 20000;
        if ($maxChars < 0) {
            $maxChars = 0;
        }

        $code = self::normalizeCode($code);

        $app = $kirby;
        $site = $kirby->site();
        $page = $site->homePage();

        $phpErrors = [];
        $resultValue = null;
        $resultJson = null;
        $resultDump = null;
        $resultDumpTruncated = false;

        $stdout = '';
        $stdoutTruncated = false;

        $start = microtime(true);
        $memStart = memory_get_usage(true);

        $exceptionPayload = null;

        // Important: prevent eval code from breaking our MCP JSON markers by leaving
        // output buffers open; flush any nested buffers back into our capture buffer.
        $baseObLevel = ob_get_level();
        ob_start();
        $captureLevel = ob_get_level();

        set_error_handler(static function (int $severity, string $message, string $file = '', int $line = 0) use (&$phpErrors): bool {
            $phpErrors[] = [
                'severity' => $severity,
                'message' => $message,
                'file' => $file,
                'line' => $line,
            ];

            return true;
        });

        try {
            $resultValue = (static function () use ($code, $kirby, $app, $site, $page, $cli) {
                return eval($code);
            })();
        } catch (Throwable $exception) {
            $exceptionPayload = self::errorArray($exception, self::traceForCli($cli, $exception));
        } finally {
            // Flush any buffers created inside eval back into our capture buffer.
            while (ob_get_level() > $captureLevel) {
                @ob_end_flush();
            }

            if (ob_get_level() === $captureLevel) {
                $stdout = (string)ob_get_clean();
            }

            // Ensure we leave the output buffering stack as we found it.
            while (ob_get_level() > $baseObLevel) {
                @ob_end_clean();
            }

            restore_error_handler();
        }

        if ($maxChars > 0 && strlen($stdout) > $maxChars) {
            $stdout = substr($stdout, 0, $maxChars);
            $stdoutTruncated = true;
        }

        $resultType = get_debug_type($resultValue);

        $encoded = null;
        try {
            $encoded = json_encode($resultValue, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (\JsonException) {
            $encoded = null;
        }

        if (is_string($encoded)) {
            try {
                $resultJson = json_decode($encoded, true, flags: JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                $resultJson = null;
            }
        }

        if ($resultJson === null) {
            $dump = null;
            try {
                $dump = var_export($resultValue, true);
            } catch (Throwable) {
                $dump = null;
            }

            if (is_string($dump)) {
                if ($maxChars > 0 && strlen($dump) > $maxChars) {
                    $dump = substr($dump, 0, $maxChars);
                    $resultDumpTruncated = true;
                }
                $resultDump = $dump;
            }
        }

        $seconds = microtime(true) - $start;
        $memBytes = memory_get_usage(true) - $memStart;

        $payload = [
            'ok' => $exceptionPayload === null,
            'enabled' => true,
            'stdout' => $stdout,
            'stdoutTruncated' => $stdoutTruncated,
            'return' => [
                'type' => $resultType,
                'json' => $resultJson,
                'dump' => $resultDump,
                'dumpTruncated' => $resultDumpTruncated,
            ],
            'phpErrors' => $phpErrors,
            'timing' => [
                'seconds' => $seconds,
                'memoryBytes' => $memBytes,
            ],
        ];

        if (is_array($exceptionPayload)) {
            $payload['error'] = $exceptionPayload;
        }

        if ($cli->arg('debug') === true) {
            $payload['code'] = $code;
        }

        self::emit($cli, $payload);
    }

    private static function isEvalEnabled(string $projectRoot): bool
    {
        $raw = getenv(self::ENV_ENABLE_EVAL);
        if (is_string($raw) && $raw !== '') {
            $normalized = strtolower(trim($raw));
            if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
                return true;
            }
        }

        return KirbyMcpConfig::load($projectRoot)->evalEnabled();
    }

    private static function normalizeCode(string $code): string
    {
        $code = trim($code);
        $code = preg_replace('/^<\\?php\\s*/i', '', $code) ?? $code;
        $code = preg_replace('/\\?>\\s*$/', '', $code) ?? $code;

        return trim($code);
    }
}
