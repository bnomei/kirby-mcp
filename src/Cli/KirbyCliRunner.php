<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Cli;

use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

final class KirbyCliRunner
{
    public const ENV_KIRBY_BIN = 'KIRBY_MCP_KIRBY_BIN';

    /**
     * @param array<int, string> $args Kirby CLI arguments (e.g. ["list"], ["make:blueprint", "post"])
     * @param array<string, string> $env Extra environment variables
     */
    public function run(
        string $projectRoot,
        array $args,
        array $env = [],
        int $timeoutSeconds = 60,
    ): KirbyCliResult {
        $binary = $this->resolveBinary($projectRoot);
        if ($binary === null) {
            throw new \RuntimeException(
                'Kirby CLI binary not found. Install getkirby/cli in the project or set ' . self::ENV_KIRBY_BIN . '.'
            );
        }

        $process = new Process(
            command: array_merge([$binary], $args),
            cwd: $projectRoot,
            env: $env + [
                // Prevent wrapped output where possible.
                'COLUMNS' => '160',
                'LINES' => '60',
            ],
        );

        $process->setTimeout($timeoutSeconds);

        try {
            $process->run();
            return new KirbyCliResult(
                exitCode: $process->getExitCode() ?? 1,
                stdout: $process->getOutput(),
                stderr: $process->getErrorOutput(),
                timedOut: false,
            );
        } catch (ProcessTimedOutException) {
            return new KirbyCliResult(
                exitCode: 124,
                stdout: $process->getOutput(),
                stderr: $process->getErrorOutput(),
                timedOut: true,
            );
        }
    }

    private function resolveBinary(string $projectRoot): ?string
    {
        $envOverride = getenv(self::ENV_KIRBY_BIN);
        if (is_string($envOverride) && $envOverride !== '') {
            $resolved = $this->resolvePath($envOverride, $projectRoot);
            if ($resolved !== null) {
                return $resolved;
            }
        }

        $startDir = is_dir($projectRoot) ? $projectRoot : dirname($projectRoot);
        $current = realpath($startDir) ?: $startDir;
        $current = rtrim($current, DIRECTORY_SEPARATOR);

        while ($current !== '') {
            $candidate = $current . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'kirby';
            if (is_file($candidate)) {
                return $candidate;
            }

            $parent = dirname($current);
            if ($parent === $current) {
                break;
            }

            $current = $parent;
        }

        return null;
    }

    private function resolvePath(string $path, string $projectRoot): ?string
    {
        if (is_file($path)) {
            return $path;
        }

        if (str_starts_with($path, DIRECTORY_SEPARATOR) === false) {
            $projectCandidate = rtrim($projectRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $path;
            if (is_file($projectCandidate)) {
                return $projectCandidate;
            }

            $cwd = getcwd();
            if (is_string($cwd) && $cwd !== '') {
                $cwdCandidate = rtrim($cwd, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $path;
                if (is_file($cwdCandidate)) {
                    return $cwdCandidate;
                }
            }
        }

        return null;
    }
}
