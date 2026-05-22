<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\OAuth;

use Bnomei\KirbyMcp\Mcp\Http\HttpAuthScopes;
use Bnomei\KirbyMcp\Project\KirbyMcpHttpConfig;
use Firebase\JWT\JWT;
use Kirby\Cms\App as Kirby;
use Kirby\Http\Response as KirbyResponse;
use Psr\Http\Message\ServerRequestInterface;

final class KirbyOAuthProvider
{
    private const AUTH_CODE_TTL = 600;
    private const ACCESS_TOKEN_TTL = 3600;
    private const REFRESH_TOKEN_TTL = 2592000;
    private const SESSION_TTL = 600;

    public function __construct(
        private readonly string $projectRoot,
        private readonly KirbyMcpHttpConfig $config,
        private readonly ServerRequestInterface $request,
    ) {
    }

    public function handle(): KirbyResponse
    {
        if ($this->config->enabled === false || $this->config->authMode !== KirbyMcpHttpConfig::AUTH_MODE_OAUTH || $this->config->oauthProvider->enabled === false) {
            return $this->error(404, 'OAuth provider is disabled.');
        }

        if ($this->isLoopbackRequest() === false && $this->isHttpsRequest() === false) {
            return $this->error(503, 'HTTP OAuth provider requires HTTPS for non-loopback requests.');
        }

        $method = $this->request->getMethod();
        $path = $this->request->getUri()->getPath();
        $providerPath = rtrim($this->config->oauthProvider->path, '/');

        if ($method === 'GET' && in_array($path, ['/.well-known/oauth-authorization-server', '/.well-known/openid-configuration'], true)) {
            return $this->authorizationServerMetadata();
        }

        if ($method === 'GET' && $path === $providerPath . '/jwks.json') {
            return $this->json($this->keySet()->jwks());
        }

        if ($method === 'POST' && $path === $providerPath . '/register') {
            return $this->registerClient();
        }

        if (in_array($method, ['GET', 'POST'], true) && $path === $providerPath . '/authorize') {
            return $this->authorize();
        }

        if ($method === 'POST' && $path === $providerPath . '/token') {
            return $this->token();
        }

        if (in_array($method, ['GET', 'POST'], true) && $path === $providerPath . '/login') {
            return $this->login();
        }

        return $this->error(404, 'OAuth provider endpoint not found.');
    }

    public function authorizationServerMetadata(): KirbyResponse
    {
        return $this->json([
            'issuer' => $this->issuer(),
            'authorization_endpoint' => $this->providerUrl('/authorize'),
            'token_endpoint' => $this->providerUrl('/token'),
            'registration_endpoint' => $this->providerUrl('/register'),
            'jwks_uri' => $this->jwksUri(),
            'response_types_supported' => ['code'],
            'grant_types_supported' => ['authorization_code', 'refresh_token'],
            'token_endpoint_auth_methods_supported' => ['none', 'client_secret_basic', 'client_secret_post'],
            'code_challenge_methods_supported' => ['S256', 'plain'],
            'scopes_supported' => $this->allowedScopes(),
        ]);
    }

    private function registerClient(): KirbyResponse
    {
        $data = $this->requestData();
        $redirectUris = $this->stringList($data['redirect_uris'] ?? null);
        if ($redirectUris === []) {
            return $this->oauthError('invalid_client_metadata', 'redirect_uris is required.', 400);
        }

        if (!$this->redirectUrisAreValid($redirectUris)) {
            return $this->oauthError('invalid_client_metadata', 'redirect_uris must be HTTPS or loopback HTTP URLs without fragments.', 400);
        }

        $authMethod = $this->stringValue($data['token_endpoint_auth_method'] ?? null) ?? 'none';
        if (!in_array($authMethod, ['none', 'client_secret_basic', 'client_secret_post'], true)) {
            return $this->oauthError('invalid_client_metadata', 'Unsupported token_endpoint_auth_method.', 400);
        }

        $invalidScope = $this->invalidScope($this->scopeString($data['scope'] ?? null));
        if ($invalidScope !== null) {
            return $this->oauthError('invalid_scope', 'Unsupported scope: ' . $invalidScope, 400);
        }

        $clientId = 'client_' . $this->randomToken(24);
        $clientSecret = $authMethod === 'none' ? null : 'secret_' . $this->randomToken(32);
        $createdAt = time();
        $scope = implode(' ', $this->finalizeScopes($this->scopeString($data['scope'] ?? null)));
        $client = [
            'client_id' => $clientId,
            'client_name' => $this->stringValue($data['client_name'] ?? null) ?? 'Claude Desktop',
            'client_secret_hash' => $clientSecret === null ? null : hash('sha256', $clientSecret),
            'token_endpoint_auth_method' => $authMethod,
            'redirect_uris' => $redirectUris,
            'grant_types' => $this->stringList($data['grant_types'] ?? null) ?: ['authorization_code', 'refresh_token'],
            'response_types' => $this->stringList($data['response_types'] ?? null) ?: ['code'],
            'scope' => $scope,
            'created_at' => $createdAt,
        ];

        $this->store()->write('clients', $clientId, $client);

        $response = [
            'client_id' => $clientId,
            'client_id_issued_at' => $createdAt,
            'client_name' => $client['client_name'],
            'redirect_uris' => $redirectUris,
            'grant_types' => $client['grant_types'],
            'response_types' => $client['response_types'],
            'token_endpoint_auth_method' => $authMethod,
            'scope' => $scope,
        ];

        if ($clientSecret !== null) {
            $response['client_secret'] = $clientSecret;
        }

        return $this->json($response, 201);
    }

    private function authorize(): KirbyResponse
    {
        $params = $this->authorizationParams();
        $validation = $this->validateAuthorizationParams($params);
        if ($validation instanceof KirbyResponse) {
            return $validation;
        }

        $user = Kirby::instance(lazy: true)?->user();
        if ($user === null) {
            $sessionId = $this->randomToken(18);
            $this->store()->write('sessions', $sessionId, [
                'params' => $params,
                'expires_at' => time() + self::SESSION_TTL,
            ]);

            return KirbyResponse::redirect($this->providerUrl('/login', ['session' => $sessionId]));
        }

        $client = $this->client((string) $params['client_id']);
        if ($client === null) {
            return $this->error(400, 'Unknown OAuth client.');
        }

        $scopes = $this->finalizeScopes((string) ($params['scope'] ?? ''));
        if ($this->needsConsent($user->id(), (string) $client['client_id'], $scopes)) {
            if ($this->request->getMethod() === 'POST') {
                return $this->completeConsent($params, $user->id(), (string) $client['client_id'], $scopes);
            }

            return $this->consentForm($client, $scopes);
        }

        $this->rememberConsent($user->id(), (string) $client['client_id'], $scopes);

        return $this->redirectWithCode($params, $user->id(), $scopes);
    }

    private function token(): KirbyResponse
    {
        $data = $this->requestData();
        $grantType = $this->stringValue($data['grant_type'] ?? null);

        return match ($grantType) {
            'authorization_code' => $this->authorizationCodeToken($data),
            'refresh_token' => $this->refreshToken($data),
            default => $this->oauthError('unsupported_grant_type', 'Unsupported grant_type.', 400),
        };
    }

    private function login(): KirbyResponse
    {
        $data = $this->requestData();
        $sessionId = $this->stringValue($data['session'] ?? $this->queryParams()['session'] ?? null);
        if ($sessionId === null || $this->readSession($sessionId) === null) {
            return $this->error(400, 'OAuth login session is missing or expired.');
        }

        if ($this->request->getMethod() === 'POST') {
            if (function_exists('csrf') && \csrf($this->stringValue($data['csrf'] ?? null) ?? '') !== true) {
                return $this->loginForm($sessionId, 'Invalid CSRF token.');
            }

            $email = $this->stringValue($data['email'] ?? null);
            $password = $this->stringValue($data['password'] ?? null);
            if ($email === null || $password === null) {
                return $this->loginForm($sessionId, 'Email and password are required.');
            }

            try {
                Kirby::instance()->auth()->login($email, $password);
            } catch (\Throwable) {
                return $this->loginForm($sessionId, 'Login failed.');
            }

            return KirbyResponse::redirect($this->providerUrl('/authorize', ['session' => $sessionId]));
        }

        return $this->loginForm($sessionId);
    }

    /**
     * @param array<string, mixed> $params
     */
    private function validateAuthorizationParams(array $params): ?KirbyResponse
    {
        if (($params['response_type'] ?? null) !== 'code') {
            return $this->error(400, 'OAuth response_type must be code.');
        }

        $clientId = $this->stringValue($params['client_id'] ?? null);
        if ($clientId === null) {
            return $this->error(400, 'OAuth client_id is required.');
        }

        $client = $this->client($clientId);
        if ($client === null) {
            return $this->error(400, 'Unknown OAuth client.');
        }

        $redirectUri = $this->redirectUri($params, $client);
        if ($redirectUri === null) {
            return $this->error(400, 'OAuth redirect_uri is not registered for this client.');
        }

        if (($client['token_endpoint_auth_method'] ?? 'none') === 'none' && $this->stringValue($params['code_challenge'] ?? null) === null) {
            return $this->redirectError($redirectUri, $this->stringValue($params['state'] ?? null), 'invalid_request', 'PKCE code_challenge is required for public clients.');
        }

        $codeChallengeMethod = $this->stringValue($params['code_challenge_method'] ?? null) ?? 'plain';
        if (!in_array($codeChallengeMethod, ['plain', 'S256'], true)) {
            return $this->redirectError($redirectUri, $this->stringValue($params['state'] ?? null), 'invalid_request', 'Unsupported code_challenge_method.');
        }

        $resource = $this->stringValue($params['resource'] ?? null);
        if ($resource !== null && $resource !== $this->audience()) {
            return $this->redirectError($redirectUri, $this->stringValue($params['state'] ?? null), 'invalid_target', 'OAuth resource does not match this MCP endpoint.');
        }

        $invalidScope = $this->invalidScope($this->scopeString($params['scope'] ?? null));
        if ($invalidScope !== null) {
            return $this->redirectError($redirectUri, $this->stringValue($params['state'] ?? null), 'invalid_scope', 'Unsupported scope: ' . $invalidScope);
        }

        return null;
    }

    /**
     * @param array<string, mixed> $params
     */
    private function redirectWithCode(array $params, string $userId, array $scopes): KirbyResponse
    {
        $client = $this->client((string) $params['client_id']);
        if ($client === null) {
            return $this->error(400, 'Unknown OAuth client.');
        }

        $redirectUri = $this->redirectUri($params, $client);
        if ($redirectUri === null) {
            return $this->error(400, 'OAuth redirect_uri is not registered for this client.');
        }

        $code = $this->randomToken(32);
        $this->store()->write('auth-codes', hash('sha256', $code), [
            'client_id' => $client['client_id'],
            'user_id' => $userId,
            'redirect_uri' => $redirectUri,
            'scopes' => array_values($scopes),
            'code_challenge' => $this->stringValue($params['code_challenge'] ?? null),
            'code_challenge_method' => $this->stringValue($params['code_challenge_method'] ?? null) ?? 'plain',
            'expires_at' => time() + self::AUTH_CODE_TTL,
        ]);

        $query = [
            'code' => $code,
        ];
        if (($state = $this->stringValue($params['state'] ?? null)) !== null) {
            $query['state'] = $state;
        }

        return KirbyResponse::redirect($this->appendQuery($redirectUri, $query));
    }

    /**
     * @param array<string, mixed> $data
     */
    private function authorizationCodeToken(array $data): KirbyResponse
    {
        $client = $this->authenticatedClient($data);
        if ($client instanceof KirbyResponse) {
            return $client;
        }

        $code = $this->stringValue($data['code'] ?? null);
        if ($code === null) {
            return $this->oauthError('invalid_request', 'code is required.', 400);
        }

        $codeId = hash('sha256', $code);
        $authCode = $this->store()->read('auth-codes', $codeId);
        if ($authCode === null || (int) ($authCode['expires_at'] ?? 0) < time()) {
            $this->store()->delete('auth-codes', $codeId);
            return $this->oauthError('invalid_grant', 'Authorization code is invalid or expired.', 400);
        }

        if (($authCode['client_id'] ?? null) !== ($client['client_id'] ?? null)) {
            return $this->oauthError('invalid_grant', 'Authorization code was not issued to this client.', 400);
        }

        if (($authCode['redirect_uri'] ?? null) !== $this->stringValue($data['redirect_uri'] ?? null)) {
            return $this->oauthError('invalid_grant', 'redirect_uri does not match authorization request.', 400);
        }

        if (!$this->verifyPkce($authCode, $this->stringValue($data['code_verifier'] ?? null))) {
            return $this->oauthError('invalid_grant', 'PKCE verification failed.', 400);
        }

        $this->store()->delete('auth-codes', $codeId);

        return $this->tokenResponse(
            (string) $client['client_id'],
            (string) $authCode['user_id'],
            $this->stringList($authCode['scopes'] ?? null),
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private function refreshToken(array $data): KirbyResponse
    {
        $client = $this->authenticatedClient($data);
        if ($client instanceof KirbyResponse) {
            return $client;
        }

        $refreshToken = $this->stringValue($data['refresh_token'] ?? null);
        if ($refreshToken === null) {
            return $this->oauthError('invalid_request', 'refresh_token is required.', 400);
        }

        $tokenId = hash('sha256', $refreshToken);
        $record = $this->store()->read('refresh-tokens', $tokenId);
        if ($record === null || (int) ($record['expires_at'] ?? 0) < time() || ($record['revoked'] ?? false) === true) {
            return $this->oauthError('invalid_grant', 'Refresh token is invalid or expired.', 400);
        }

        if (($record['client_id'] ?? null) !== ($client['client_id'] ?? null)) {
            return $this->oauthError('invalid_grant', 'Refresh token was not issued to this client.', 400);
        }

        $requestedScopes = $this->finalizeScopes($this->scopeString($data['scope'] ?? null));
        $originalScopes = $this->stringList($record['scopes'] ?? null);
        if ($requestedScopes === []) {
            $requestedScopes = $originalScopes;
        }

        foreach ($requestedScopes as $scope) {
            if (!in_array($scope, $originalScopes, true)) {
                return $this->oauthError('invalid_scope', 'Refresh token scope cannot be expanded.', 400);
            }
        }

        $record['revoked'] = true;
        $record['revoked_at'] = time();
        $this->store()->write('refresh-tokens', $tokenId, $record);

        return $this->tokenResponse((string) $client['client_id'], (string) $record['user_id'], $requestedScopes);
    }

    /**
     * @param list<string> $scopes
     */
    private function tokenResponse(string $clientId, string $userId, array $scopes): KirbyResponse
    {
        $now = time();
        $jti = $this->randomToken(18);
        $scope = implode(' ', $scopes);
        $accessToken = JWT::encode([
            'iss' => $this->issuer(),
            'sub' => $userId,
            'aud' => $this->audience(),
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + self::ACCESS_TOKEN_TTL,
            'jti' => $jti,
            'client_id' => $clientId,
            'scope' => $scope,
        ], $this->keySet()->privateKey(), 'RS256', $this->keySet()->kid());

        $refreshToken = 'refresh_' . $this->randomToken(40);
        $this->store()->write('refresh-tokens', hash('sha256', $refreshToken), [
            'client_id' => $clientId,
            'user_id' => $userId,
            'scopes' => $scopes,
            'access_token_id' => $jti,
            'expires_at' => $now + self::REFRESH_TOKEN_TTL,
            'revoked' => false,
        ]);

        return $this->json([
            'token_type' => 'Bearer',
            'expires_in' => self::ACCESS_TOKEN_TTL,
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'scope' => $scope,
        ], 200, [
            'Cache-Control' => 'no-store',
            'Pragma' => 'no-cache',
        ]);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>|KirbyResponse
     */
    private function authenticatedClient(array $data): array|KirbyResponse
    {
        [$basicClientId, $basicSecret] = $this->basicAuthClient();
        $clientId = $basicClientId ?? $this->stringValue($data['client_id'] ?? null);
        if ($clientId === null) {
            return $this->oauthError('invalid_client', 'client_id is required.', 401);
        }

        $client = $this->client($clientId);
        if ($client === null) {
            return $this->oauthError('invalid_client', 'Unknown client.', 401);
        }

        $method = $client['token_endpoint_auth_method'] ?? 'none';
        if ($method === 'none') {
            return $client;
        }

        $secret = $basicSecret ?? $this->stringValue($data['client_secret'] ?? null);
        if (!is_string($secret) || !hash_equals((string) ($client['client_secret_hash'] ?? ''), hash('sha256', $secret))) {
            return $this->oauthError('invalid_client', 'Invalid client credentials.', 401);
        }

        return $client;
    }

    /**
     * @return array{0: string|null, 1: string|null}
     */
    private function basicAuthClient(): array
    {
        $header = $this->request->getHeaderLine('Authorization');
        if (!preg_match('/^Basic\s+(.+)$/i', $header, $matches)) {
            return [null, null];
        }

        $decoded = base64_decode($matches[1], true);
        if (!is_string($decoded) || !str_contains($decoded, ':')) {
            return [null, null];
        }

        [$clientId, $secret] = explode(':', $decoded, 2);

        return [rawurldecode($clientId), rawurldecode($secret)];
    }

    /**
     * @param array<string, mixed> $authCode
     */
    private function verifyPkce(array $authCode, ?string $verifier): bool
    {
        $challenge = $this->stringValue($authCode['code_challenge'] ?? null);
        if ($challenge === null) {
            return true;
        }

        if ($verifier === null || !preg_match('/^[A-Za-z0-9._~-]{43,128}$/', $verifier)) {
            return false;
        }

        $method = $this->stringValue($authCode['code_challenge_method'] ?? null) ?? 'plain';
        $actual = $method === 'S256' ? OAuthKeySet::base64Url(hash('sha256', $verifier, true)) : $verifier;

        return hash_equals($challenge, $actual);
    }

    /**
     * @return array<string, mixed>
     */
    private function authorizationParams(): array
    {
        $sessionId = $this->stringValue($this->queryParams()['session'] ?? null);
        if ($sessionId !== null) {
            $session = $this->readSession($sessionId);
            if ($session !== null && is_array($session['params'] ?? null)) {
                /** @var array<string, mixed> $params */
                $params = $session['params'];
                return $params;
            }
        }

        return $this->request->getMethod() === 'POST' ? $this->requestData() : $this->queryParams();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function readSession(string $sessionId): ?array
    {
        $session = $this->store()->read('sessions', $sessionId);
        if ($session === null || (int) ($session['expires_at'] ?? 0) < time()) {
            $this->store()->delete('sessions', $sessionId);
            return null;
        }

        return $session;
    }

    /**
     * @param list<string> $scopes
     */
    private function needsConsent(string $userId, string $clientId, array $scopes): bool
    {
        if ($this->config->oauthProvider->consent === 'auto') {
            return false;
        }

        if ($this->config->oauthProvider->consent === 'remember' && $this->store()->read('consents', $this->consentId($userId, $clientId, $scopes)) !== null) {
            return false;
        }

        return true;
    }

    /**
     * @param array<string, mixed> $params
     * @param list<string> $scopes
     */
    private function completeConsent(array $params, string $userId, string $clientId, array $scopes): KirbyResponse
    {
        $data = $this->requestData();
        if (function_exists('csrf') && \csrf($this->stringValue($data['csrf'] ?? null) ?? '') !== true) {
            return $this->consentForm($this->client($clientId) ?? [], $scopes, 'Invalid CSRF token.');
        }

        if (isset($data['deny'])) {
            $client = $this->client($clientId);
            $redirectUri = $client === null ? null : $this->redirectUri($params, $client);
            if ($redirectUri === null) {
                return $this->error(400, 'OAuth redirect_uri is not registered for this client.');
            }

            return $this->redirectError($redirectUri, $this->stringValue($params['state'] ?? null), 'access_denied', 'Authorization was denied.');
        }

        $this->rememberConsent($userId, $clientId, $scopes);

        return $this->redirectWithCode($params, $userId, $scopes);
    }

    /**
     * @param list<string> $scopes
     */
    private function rememberConsent(string $userId, string $clientId, array $scopes): void
    {
        $this->store()->write('consents', $this->consentId($userId, $clientId, $scopes), [
            'user_id' => $userId,
            'client_id' => $clientId,
            'scopes' => $scopes,
            'approved_at' => time(),
        ]);
    }

    /**
     * @param list<string> $scopes
     */
    private function consentId(string $userId, string $clientId, array $scopes): string
    {
        sort($scopes);

        return hash('sha256', $userId . '|' . $clientId . '|' . implode(' ', $scopes));
    }

    /**
     * @param array<string, mixed> $client
     * @param list<string> $scopes
     */
    private function consentForm(array $client, array $scopes, ?string $error = null): KirbyResponse
    {
        $data = [
            'client' => $client,
            'scopes' => $scopes,
            'user' => Kirby::instance(lazy: true)?->user(),
            'approveUrl' => (string) $this->request->getUri(),
            'denyUrl' => (string) $this->request->getUri(),
            'error' => $error,
        ];

        if ($this->config->oauthProvider->consent === 'snippet' && function_exists('snippet')) {
            $html = \snippet($this->config->oauthProvider->consentSnippet, $data, true);
            if (is_string($html) && trim($html) !== '') {
                return new KirbyResponse($html, 'text/html', 200);
            }
        }

        $csrf = function_exists('csrf') ? (string) \csrf() : '';
        $clientName = htmlspecialchars((string) ($client['client_name'] ?? $client['client_id'] ?? 'OAuth client'), ENT_QUOTES, 'UTF-8');
        $scopeText = htmlspecialchars(implode(', ', $scopes), ENT_QUOTES, 'UTF-8');
        $errorHtml = $error === null ? '' : '<p>' . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . '</p>';

        return new KirbyResponse('<!doctype html><meta charset="utf-8"><title>Authorize Kirby MCP</title>' . $errorHtml . '<h1>Authorize ' . $clientName . '</h1><p>' . $scopeText . '</p><form method="post"><input type="hidden" name="csrf" value="' . htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') . '"><button type="submit" name="approve" value="1">Approve</button><button type="submit" name="deny" value="1">Deny</button></form>', 'text/html', 200);
    }

    private function loginForm(string $sessionId, ?string $error = null): KirbyResponse
    {
        $csrf = function_exists('csrf') ? (string) \csrf() : '';
        $errorHtml = $error === null ? '' : '<p>' . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . '</p>';

        return new KirbyResponse('<!doctype html><meta charset="utf-8"><title>Kirby MCP Login</title>' . $errorHtml . '<h1>Kirby MCP Login</h1><form method="post"><input type="hidden" name="session" value="' . htmlspecialchars($sessionId, ENT_QUOTES, 'UTF-8') . '"><input type="hidden" name="csrf" value="' . htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') . '"><label>Email <input type="email" name="email" required></label><label>Password <input type="password" name="password" required></label><button type="submit">Log in</button></form>', 'text/html', 200);
    }

    /**
     * @param array<string, mixed> $params
     * @param array<string, mixed> $client
     */
    private function redirectUri(array $params, array $client): ?string
    {
        $registered = $this->stringList($client['redirect_uris'] ?? null);
        $redirectUri = $this->stringValue($params['redirect_uri'] ?? null);
        if ($redirectUri === null && count($registered) === 1) {
            $redirectUri = $registered[0];
        }

        if ($redirectUri === null || !in_array($redirectUri, $registered, true)) {
            return null;
        }

        return $redirectUri;
    }

    /**
     * @param list<string> $redirectUris
     */
    private function redirectUrisAreValid(array $redirectUris): bool
    {
        foreach ($redirectUris as $redirectUri) {
            $parts = parse_url($redirectUri);
            if (!is_array($parts)) {
                return false;
            }

            if (isset($parts['fragment'])) {
                return false;
            }

            $scheme = strtolower((string) ($parts['scheme'] ?? ''));
            $host = strtolower(trim((string) ($parts['host'] ?? ''), " \t\n\r\0\x0B[]"));
            if ($scheme === 'https' && $host !== '') {
                continue;
            }

            if ($scheme === 'http' && ($host === 'localhost' || $host === '127.0.0.1' || str_starts_with($host, '127.'))) {
                continue;
            }

            return false;
        }

        return true;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function client(string $clientId): ?array
    {
        return $this->store()->read('clients', $clientId);
    }

    /**
     * @return list<string>
     */
    private function finalizeScopes(string $scope): array
    {
        $requested = $this->scopeList($scope);
        if ($requested === []) {
            return $this->allowedScopes();
        }

        return array_values(array_intersect($requested, $this->allowedScopes()));
    }

    private function invalidScope(string $scope): ?string
    {
        foreach ($this->scopeList($scope) as $item) {
            if (!in_array($item, $this->allowedScopes(), true)) {
                return $item;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function allowedScopes(): array
    {
        return $this->config->scopes === [] ? HttpAuthScopes::all() : $this->config->scopes;
    }

    /**
     * @return list<string>
     */
    private function scopeList(string $scope): array
    {
        $items = preg_split('/\s+/', trim($scope)) ?: [];
        $items = array_values(array_filter($items, static fn (string $item): bool => $item !== ''));

        return array_values(array_unique($items));
    }

    private function scopeString(mixed $scope): string
    {
        if (is_string($scope)) {
            return $scope;
        }

        if (is_array($scope)) {
            return implode(' ', $this->stringList($scope));
        }

        return '';
    }

    /**
     * @return array<string, mixed>
     */
    private function requestData(): array
    {
        $body = $this->rawBody();
        if ($body !== '' && str_contains(strtolower($this->request->getHeaderLine('Content-Type')), 'json')) {
            $json = json_decode($body, true);
            if (is_array($json)) {
                return $json;
            }
        }

        $parsed = $this->request->getParsedBody();
        if (is_array($parsed)) {
            return $parsed;
        }

        if (trim($body) === '') {
            return [];
        }

        $json = json_decode($body, true);
        if (is_array($json)) {
            return $json;
        }

        parse_str($body, $data);

        return is_array($data) ? $data : [];
    }

    private function rawBody(): string
    {
        $body = $this->request->getBody();
        if ($body->isSeekable()) {
            $body->rewind();
        }

        return (string) $body;
    }

    /**
     * @return array<string, mixed>
     */
    private function queryParams(): array
    {
        $queryParams = $this->request->getQueryParams();
        if ($queryParams !== []) {
            return $queryParams;
        }

        $query = $this->request->getUri()->getQuery();
        if ($query === '') {
            return [];
        }

        parse_str($query, $data);

        return is_array($data) ? $data : [];
    }

    private function issuer(): string
    {
        return rtrim((string) $this->config->oauthIssuer, '/');
    }

    private function audience(): string
    {
        return (string) $this->config->oauthAudience;
    }

    private function jwksUri(): string
    {
        return (string) $this->config->oauthJwksUri;
    }

    private function providerUrl(string $path, array $query = []): string
    {
        $url = rtrim($this->issuer(), '/') . rtrim($this->config->oauthProvider->path, '/') . '/' . ltrim($path, '/');

        return $query === [] ? $url : $this->appendQuery($url, $query);
    }

    /**
     * @param array<string, string> $query
     */
    private function appendQuery(string $url, array $query): string
    {
        $separator = str_contains($url, '?') ? '&' : '?';

        return $url . $separator . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    private function store(): OAuthFileStore
    {
        return new OAuthFileStore(
            rtrim($this->projectRoot, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR . '.kirby-mcp'
            . DIRECTORY_SEPARATOR . 'oauth',
        );
    }

    private function keySet(): OAuthKeySet
    {
        return new OAuthKeySet($this->store());
    }

    private function randomToken(int $bytes): string
    {
        if ($bytes < 1) {
            throw new \InvalidArgumentException('Token byte length must be at least 1.');
        }

        return OAuthKeySet::base64Url(random_bytes($bytes));
    }

    /**
     * @return list<string>
     */
    private function stringList(mixed $value): array
    {
        if (is_string($value)) {
            return [$value];
        }

        if (!is_array($value)) {
            return [];
        }

        $out = [];
        foreach ($value as $item) {
            if (is_string($item) && trim($item) !== '') {
                $out[] = trim($item);
            }
        }

        return array_values(array_unique($out));
    }

    private function stringValue(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $headers
     */
    private function json(array $data, int $code = 200, array $headers = []): KirbyResponse
    {
        return KirbyResponse::json($data, $code, false, $headers);
    }

    private function error(int $code, string $message): KirbyResponse
    {
        return $this->json([
            'ok' => false,
            'error' => [
                'message' => $message,
            ],
        ], $code);
    }

    private function oauthError(string $error, string $description, int $code): KirbyResponse
    {
        return $this->json([
            'error' => $error,
            'error_description' => $description,
        ], $code, [
            'Cache-Control' => 'no-store',
            'Pragma' => 'no-cache',
        ]);
    }

    private function redirectError(string $redirectUri, ?string $state, string $error, string $description): KirbyResponse
    {
        $query = [
            'error' => $error,
            'error_description' => $description,
        ];
        if ($state !== null) {
            $query['state'] = $state;
        }

        return KirbyResponse::redirect($this->appendQuery($redirectUri, $query));
    }

    private function isLoopbackRequest(): bool
    {
        $remoteAddress = $this->request->getServerParams()['REMOTE_ADDR'] ?? null;
        if (!is_string($remoteAddress) || trim($remoteAddress) === '') {
            return false;
        }

        $remoteAddress = strtolower(trim($remoteAddress, " \t\n\r\0\x0B[]"));
        $host = strtolower(trim($this->request->getUri()->getHost(), " \t\n\r\0\x0B[]"));

        return ($remoteAddress === '::1' || $remoteAddress === '127.0.0.1' || str_starts_with($remoteAddress, '127.'))
            && ($host === 'localhost' || $host === '::1' || $host === '127.0.0.1' || str_starts_with($host, '127.'));
    }

    private function isHttpsRequest(): bool
    {
        if (strtolower($this->request->getUri()->getScheme()) === 'https') {
            return true;
        }

        $https = $this->request->getServerParams()['HTTPS'] ?? null;

        return is_string($https) && in_array(strtolower($https), ['1', 'on', 'true'], true);
    }
}
