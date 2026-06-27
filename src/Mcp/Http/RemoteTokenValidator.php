<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Http;

use Bnomei\KirbyMcp\Project\KirbyMcpHttpToken;
use Mcp\Server\Transport\Http\OAuth\AuthorizationResult;
use Mcp\Server\Transport\Http\OAuth\AuthorizationTokenValidatorInterface;

final readonly class RemoteTokenValidator implements AuthorizationTokenValidatorInterface
{
    /**
     * @param list<KirbyMcpHttpToken> $tokens
     */
    public function __construct(
        private array $tokens,
    ) {
    }

    public function validate(string $accessToken): AuthorizationResult
    {
        $presentedHash = KirbyMcpHttpToken::hashPlainText($accessToken);
        $matchedToken = null;

        foreach ($this->tokens as $token) {
            if (!$token instanceof KirbyMcpHttpToken || !$token->hasValidHash()) {
                continue;
            }

            if (hash_equals($token->normalizedHash(), $presentedHash)) {
                $matchedToken ??= $token;
            }
        }

        if (!$matchedToken instanceof KirbyMcpHttpToken) {
            return AuthorizationResult::unauthorized('invalid_token', 'Invalid bearer token.');
        }

        // An empty scope list means "unspecified", not "grant everything".
        // Default to least privilege (read-only) so a missing/omitted `scopes`
        // field cannot silently elevate a token to write/execute/admin. Full
        // access must be opted into by listing scopes explicitly.
        $scopes = $matchedToken->scopes === [] ? [HttpAuthScopes::READ] : $matchedToken->scopes;

        return AuthorizationResult::allow([
            'oauth.claims' => [
                'token_id' => $matchedToken->id,
                'token_type' => 'remote-token',
            ],
            'oauth.scopes' => $scopes,
            'oauth.subject' => 'remote-token:' . $matchedToken->id,
        ]);
    }
}
