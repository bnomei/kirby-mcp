<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Support;

interface RuntimeContextInterface
{
    public function projectRoot(): string;

    public function host(): ?string;

    /**
     * @return array<string, string>
     */
    public function env(): array;

    public function commandsRoot(): string;

    public function commandFile(string $relativePath): string;
}
