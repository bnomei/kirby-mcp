<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Support;

use Bnomei\KirbyMcp\Cli\KirbyCliResult;

/**
 * Normalized result for running a Kirby runtime CLI command (mcp:*) via KirbyCliRunner.
 */
final readonly class RuntimeCommandResult
{
    public const DEBUG_RETRY_MESSAGE = 'Retry with debug=true to include CLI stdout/stderr.';

    /**
     * @param array<mixed>|null $payload
     */
    public function __construct(
        public string $projectRoot,
        public ?string $host,
        public string $commandsRoot,
        public string $expectedCommandFile,
        public bool $installed,
        public ?KirbyCliResult $cliResult = null,
        public ?array $payload = null,
        public ?string $parseError = null,
    ) {
    }

    /**
     * @return array{exitCode:int, timedOut:bool}|null
     */
    public function cliMeta(): ?array
    {
        if ($this->cliResult === null) {
            return null;
        }

        return [
            'exitCode' => $this->cliResult->exitCode,
            'timedOut' => $this->cliResult->timedOut,
        ];
    }

    /**
     * @return array{exitCode:int, stdout:string, stderr:string, timedOut:bool}|null
     */
    public function cli(): ?array
    {
        return $this->cliResult?->toArray();
    }

    public function parseErrorString(): string
    {
        $error = $this->parseError ?? RuntimeCommandRunner::DEFAULT_PARSE_ERROR;
        $error = trim($error);

        return $error !== '' ? $error : RuntimeCommandRunner::DEFAULT_PARSE_ERROR;
    }

    /**
     * Standard response for missing runtime command wrappers.
     *
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    public function needsRuntimeInstallResponse(array $extra = []): array
    {
        return array_merge([
            'ok' => false,
            'needsRuntimeInstall' => true,
            'message' => RuntimeCommandRunner::NEEDS_RUNTIME_INSTALL_MESSAGE,
            'expectedCommandFile' => $this->expectedCommandFile,
        ], $extra);
    }

    /**
     * Standard response for runtime parse errors.
     *
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    public function parseErrorResponse(array $extra = []): array
    {
        return array_merge([
            'ok' => false,
            'parseError' => $this->parseErrorString(),
        ], $extra);
    }
}
