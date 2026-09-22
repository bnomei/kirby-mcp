<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Cli;

final readonly class PhpBinaryResolver
{
    public function __construct(
        private string $phpBinary = PHP_BINARY,
        private string $phpBinDir = PHP_BINDIR,
    ) {
    }

    public function resolve(string|false $envOverride): string
    {
        if (is_string($envOverride)) {
            $override = $this->normalize($envOverride);
            if ($override !== '') {
                return $override;
            }
        }

        $phpBinary = trim($this->phpBinary);
        if ($phpBinary !== '') {
            return $phpBinary;
        }

        $candidate = rtrim($this->phpBinDir, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . (PHP_OS_FAMILY === 'Windows' ? 'php.exe' : 'php');
        if (is_file($candidate) && is_executable($candidate)) {
            return $candidate;
        }

        throw new \RuntimeException(
            'Unable to resolve a PHP CLI binary. Set ' . KirbyCliRunner::ENV_PHP_BINARY . ' to an executable PHP CLI path.'
        );
    }

    private function normalize(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $quote = $value[0];
        if (($quote === '"' || $quote === "'") && str_ends_with($value, $quote)) {
            return trim(substr($value, 1, -1));
        }

        return $value;
    }
}
