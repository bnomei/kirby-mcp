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
     * @param array<string, mixed> $scripts
     * @param array<string, ToolDetection> $tools
     */
    public function __construct(
        public string $projectRoot,
        public array $composerJson,
        public array $scripts,
        public array $tools,
    ) {
    }

    /**
     * @return array{
     *   projectRoot: string,
     *   composerJson: array<mixed>,
     *   scripts: array<string, mixed>,
     *   tools: array<string, ToolDetection>
     * }
     */
    public function toArray(): array
    {
        return [
            'projectRoot' => $this->projectRoot,
            'composerJson' => $this->composerJson,
            'scripts' => $this->scripts,
            'tools' => $this->tools,
        ];
    }
}
