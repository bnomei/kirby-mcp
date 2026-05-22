<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\OAuth;

use Mcp\Server\Transport\Http\OAuth\JwksProviderInterface;

final readonly class StaticJwksProvider implements JwksProviderInterface
{
    /**
     * @param array<string, mixed> $jwks
     */
    public function __construct(
        private array $jwks,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getJwks(string $issuer, ?string $jwksUri = null): array
    {
        return $this->jwks;
    }
}
