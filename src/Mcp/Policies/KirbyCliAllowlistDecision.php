<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Policies;

final readonly class KirbyCliAllowlistDecision
{
    public function __construct(
        public bool $allowed,
        public ?string $matchedDeny,
        public ?string $matchedAllow,
        public ?string $matchedAllowWrite,
    ) {
    }

    /**
     * @return array{matchedDeny:string|null, matchedAllow:string|null, matchedAllowWrite:string|null}
     */
    public function toArray(): array
    {
        return [
            'matchedDeny' => $this->matchedDeny,
            'matchedAllow' => $this->matchedAllow,
            'matchedAllowWrite' => $this->matchedAllowWrite,
        ];
    }

    public function requiresAllowWrite(): bool
    {
        return $this->allowed === false
            && $this->matchedDeny === null
            && $this->matchedAllow === null
            && $this->matchedAllowWrite !== null;
    }
}
