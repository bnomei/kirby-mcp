<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Http;

use Mcp\Server\Authorization\AccessToken;
use Mcp\Server\Transport\Http\OAuth\AuthorizationResult;
use Mcp\Server\Transport\Http\OAuth\AuthorizationTokenValidatorInterface;

final readonly class SharedTokenValidator implements AuthorizationTokenValidatorInterface
{
    /**
     * @param list<string> $scopes
     */
    public function __construct(
        private string $sharedToken,
        private array $scopes = [],
    ) {
    }

    public function validate(string $accessToken): AuthorizationResult
    {
        if ($this->sharedToken === '' || !hash_equals($this->sharedToken, $accessToken)) {
            return AuthorizationResult::unauthorized('invalid_token', 'Invalid bearer token.');
        }

        $scopes = $this->scopes === [] ? HttpAuthScopes::all() : $this->scopes;

        return AuthorizationResult::allow(new AccessToken($scopes, [
            'token_type' => 'shared-token',
            'sub' => 'shared-token',
        ]));
    }
}
