<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Install;

final readonly class RuntimeCommandsInstallResult
{
    /**
     * @param array<int, string> $installed
     * @param array<int, string> $skipped
     * @param array<int, array{path: string, error: string}> $errors
     */
    public function __construct(
        public string $projectRoot,
        public string $commandsRoot,
        public array $installed,
        public array $skipped,
        public array $errors,
    ) {
    }

    /**
     * The install is non-transactional (per-file), so it can finish with some
     * `installed` paths and a non-empty `errors` list. Success means every
     * targeted file was written: no per-file errors.
     */
    public function ok(): bool
    {
        return $this->errors === [];
    }

    /**
     * @return array{
     *   ok: bool,
     *   projectRoot: string,
     *   commandsRoot: string,
     *   installed: array<int, string>,
     *   skipped: array<int, string>,
     *   errors: array<int, array{path: string, error: string}>
     * }
     */
    public function toArray(): array
    {
        return [
            'ok' => $this->ok(),
            'projectRoot' => $this->projectRoot,
            'commandsRoot' => $this->commandsRoot,
            'installed' => $this->installed,
            'skipped' => $this->skipped,
            'errors' => $this->errors,
        ];
    }
}
