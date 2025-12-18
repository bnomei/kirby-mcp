<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Project;

/**
 * @phpstan-type LocalRunner 'ddev'|'docker'|'herd'|'php'|'unknown'
 */
final readonly class EnvironmentInfo
{
    /**
     * @param array<string, string> $signals
     */
    public function __construct(
        public string $projectRoot,
        public string $localRunner,
        public array $signals,
    ) {
    }

    /**
     * @return array{
     *   projectRoot: string,
     *   localRunner: string,
     *   signals: array<string, string>
     * }
     */
    public function toArray(): array
    {
        return [
            'projectRoot' => $this->projectRoot,
            'localRunner' => $this->localRunner,
            'signals' => $this->signals,
        ];
    }
}
