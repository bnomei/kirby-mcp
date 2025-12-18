<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Project;

/**
 * @phpstan-type ToolDetection array{
 *   tool: string,
 *   present: bool,
 *   via?: 'require'|'require-dev'|'script'|'bin',
 *   run?: string
 * }
 */
final readonly class ComposerAudit
{
    /**
     * @param array<mixed> $composerJson
     * @param array<mixed>|null $composerLock
     * @param array<string, mixed> $scripts
     * @param array<string, ToolDetection> $tools
     */
    public function __construct(
        public string $projectRoot,
        public array $composerJson,
        public ?array $composerLock,
        public array $scripts,
        public array $tools,
    ) {
    }

    /**
     * @return array{
     *   projectRoot: string,
     *   composerJson: array<mixed>,
     *   composerLock: array<mixed>|null,
     *   scripts: array<string, mixed>,
     *   tools: array<string, ToolDetection>
     * }
     */
    public function toArray(): array
    {
        return [
            'projectRoot' => $this->projectRoot,
            'composerJson' => $this->composerJson,
            'composerLock' => $this->composerLock,
            'scripts' => $this->scripts,
            'tools' => $this->tools,
        ];
    }
}
