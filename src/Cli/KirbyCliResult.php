<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Cli;

final readonly class KirbyCliResult
{
    public function __construct(
        public int $exitCode,
        public string $stdout,
        public string $stderr,
        public bool $timedOut = false,
    ) {
    }

    /**
     * @return array{exitCode:int, stdout:string, stderr:string, timedOut:bool}
     */
    public function toArray(): array
    {
        return [
            'exitCode' => $this->exitCode,
            'stdout' => $this->stdout,
            'stderr' => $this->stderr,
            'timedOut' => $this->timedOut,
        ];
    }
}
