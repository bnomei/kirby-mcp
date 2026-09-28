<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Install;

final readonly class PluginAdapterInstallResult
{
    /**
     * @param array<int, string> $installed
     * @param array<int, string> $skipped
     * @param array<int, array{path: string, error: string}> $errors
     */
    public function __construct(
        public string $root,
        public array $installed,
        public array $skipped,
        public array $errors,
    ) {
    }

    /** @return array{root: string, installed: array<int, string>, skipped: array<int, string>, errors: array<int, array{path: string, error: string}>} */
    public function toArray(): array
    {
        return [
            'root' => $this->root,
            'installed' => $this->installed,
            'skipped' => $this->skipped,
            'errors' => $this->errors,
        ];
    }
}
