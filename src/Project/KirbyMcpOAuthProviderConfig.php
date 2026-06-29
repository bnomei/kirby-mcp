<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Project;

final readonly class KirbyMcpOAuthProviderConfig
{
    public const DEFAULT_ENABLED = false;
    public const DEFAULT_PATH = '/mcp/oauth';
    public const DEFAULT_CONSENT = 'snippet';
    public const DEFAULT_CONSENT_SNIPPET = 'kirby-mcp/oauth-consent';

    public const DEFAULT_ROLE = 'admin';

    /**
     * @param 'auto'|'remember'|'always'|'snippet' $consent
     */
    public function __construct(
        public bool $enabled = self::DEFAULT_ENABLED,
        public string $path = self::DEFAULT_PATH,
        public string $consent = self::DEFAULT_CONSENT,
        public string $consentSnippet = self::DEFAULT_CONSENT_SNIPPET,
        public string $role = self::DEFAULT_ROLE,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function validationErrors(): array
    {
        $errors = [];

        if ($this->path === '' || !str_starts_with($this->path, '/')) {
            $errors[] = 'HTTP OAuth provider path must start with /.';
        }

        if (!in_array($this->consent, ['auto', 'remember', 'always', 'snippet'], true)) {
            $errors[] = 'HTTP OAuth provider consent must be auto, remember, always, or snippet.';
        }

        if (trim($this->consentSnippet) === '') {
            $errors[] = 'HTTP OAuth provider consent snippet must not be empty.';
        }

        if (trim($this->role) === '') {
            $errors[] = 'HTTP OAuth provider role must not be empty.';
        }

        return $errors;
    }
}
