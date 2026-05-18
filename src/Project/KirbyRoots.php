<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Project;

final readonly class KirbyRoots
{
    /**
     * @param array<string, string> $roots
     */
    public function __construct(public array $roots)
    {
    }

    public function get(string $key): ?string
    {
        return $this->roots[$key] ?? null;
    }

    public function commandsRoot(): ?string
    {
        return $this->get('commands.local') ?? $this->get('commands') ?? null;
    }

    /**
     * Parses the output of `kirby roots`, which uses PHP array dump formats.
     */
    public static function fromCliOutput(string $output): self
    {
        $roots = [];

        $pattern = '/(?:\\["([^"]+)"\\]|\'([^\']+)\')\\s*=>\\s*\\R\\s*string\\(\\d+\\)\\s*"([^"]*)"/m';
        if (preg_match_all($pattern, $output, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $key = $match[1] !== '' ? $match[1] : $match[2];
                $roots[$key] = $match[3];
            }
        }

        /** @var array<string, string> $roots */
        return new self($roots);
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return $this->roots;
    }
}
