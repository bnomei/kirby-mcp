<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Project;

final readonly class KirbyMcpHttpToken
{
    /**
     * @param list<string> $scopes
     */
    public function __construct(
        public string $id,
        public string $hash,
        public string $userId,
        public array $scopes = [],
    ) {
    }

    /**
     * @param list<string> $scopes
     */
    public static function fromPlainText(string $id, string $token, string $userId, array $scopes = []): self
    {
        return new self($id, self::hashPlainText($token), $userId, $scopes);
    }

    public static function hashPlainText(string $token): string
    {
        return 'sha256:' . hash('sha256', $token);
    }

    public function normalizedHash(): string
    {
        return strtolower(trim($this->hash));
    }

    public function hasValidHash(): bool
    {
        $hash = $this->normalizedHash();

        return str_starts_with($hash, 'sha256:')
            && strlen($hash) === 71
            && ctype_xdigit(substr($hash, 7));
    }

    public function hasValidUserId(): bool
    {
        return trim($this->userId) !== '' && !str_contains($this->userId, "\0");
    }
}
