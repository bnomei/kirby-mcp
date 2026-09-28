<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp;

use Bnomei\KirbyMcp\Project\KirbyMcpConfig;

final readonly class Activity
{
    public function __construct(private string $projectRoot)
    {
    }

    public function record(): void
    {
        if (!$this->enabled()) {
            return;
        }

        // Best effort only: activity must never turn a successful MCP operation into an error.
        $file = @fopen($this->path(), 'c+');
        if ($file === false) {
            return;
        }
        try {
            if (@flock($file, LOCK_EX | LOCK_NB)) {
                @ftruncate($file, 0);
                @fwrite($file, (string) time());
                @fflush($file);
                flock($file, LOCK_UN);
            }
        } finally {
            fclose($file);
        }
    }

    /** @return array{state: string, ageSeconds: int|null} */
    public function status(?int $now = null): array
    {
        $hidden = ['state' => 'hidden', 'ageSeconds' => null];
        if (!$this->enabled()) {
            return $hidden;
        }
        $file = @fopen($this->path(), 'r');
        if ($file === false) {
            return $hidden;
        }
        try {
            if (!@flock($file, LOCK_SH | LOCK_NB)) {
                return $hidden;
            }
            $value = @stream_get_contents($file, 32);
            flock($file, LOCK_UN);
        } finally {
            fclose($file);
        }
        if (!is_string($value) || !ctype_digit($value)) {
            return $hidden;
        }
        $age = ($now ?? time()) - (int) $value;
        if ($age < 0 || $age >= 300) {
            return $hidden;
        }

        return ['state' => $age < 30 ? 'active' : ($age < 120 ? 'recent' : 'idle'), 'ageSeconds' => $age];
    }

    private function enabled(): bool
    {
        return (KirbyMcpConfig::load($this->projectRoot)->data['activity']['enabled'] ?? false) === true;
    }

    private function path(): string
    {
        return $this->projectRoot . '/.kirby-mcp/activity';
    }
}
