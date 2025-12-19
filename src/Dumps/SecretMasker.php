<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Dumps;

final class SecretMasker
{
    private const MASK = '[REDACTED]';

    /**
     * Default patterns for common secrets/sensitive data.
     * Each pattern includes a name for documentation and a regex.
     *
     * @var array<string, string>
     */
    public const DEFAULT_PATTERNS = [
        // API Keys & Tokens
        'OpenAI API Key' => '/\bsk-(?:proj-)?[a-zA-Z0-9\-_]{20,}\b/',  // sk- or sk-proj- followed by 20+ chars
        'Anthropic API Key' => '/\bsk-ant-api\d{0,2}-[a-zA-Z0-9\-]{80,120}\b/',
        'Generic SK API Key' => '/\bsk-[a-zA-Z0-9\-_]{32,}\b/',  // Catch-all for sk- keys (32+ chars)
        'Fireworks API Key' => '/\bfw_[a-zA-Z0-9]{24}\b/',
        'Slack App Token' => '/\bxapp-[0-9]+-[A-Za-z0-9_]+-[0-9]+-[a-f0-9]+\b/',
        'Google API Key' => '/\bAIza[0-9A-Za-z\-_]{35}\b/',
        'Stripe Key' => '/\b(?:r|s)k_(test|live)_[0-9a-zA-Z]{24,}\b/',

        // GitHub Tokens
        'GitHub Classic PAT' => '/\bghp_[A-Za-z0-9]{36}\b/',
        'GitHub Fine-Grained PAT' => '/\bgithub_pat_[A-Za-z0-9_]{22,}\b/',  // Variable length after prefix
        'GitHub OAuth Token' => '/\bgho_[A-Za-z0-9]{36}\b/',
        'GitHub User-to-Server Token' => '/\bghu_[A-Za-z0-9]{36}\b/',
        'GitHub Server-to-Server Token' => '/\bghs_[A-Za-z0-9]{36}\b/',

        // AWS
        'AWS Access Key ID' => '/\b(AKIA|A3T|AGPA|AIDA|AROA|AIPA|ANPA|ANVA|ASIA)[A-Z0-9]{12,}\b/',

        // JWT (common format)
        'JWT' => '/\bey[a-zA-Z0-9_\-=]{10,}\.[a-zA-Z0-9_\-=]{10,}\.[a-zA-Z0-9_\-=]{10,}\b/',

        // Firebase
        'Firebase Auth Domain' => '/\b[a-z0-9\-]{1,30}\.firebaseapp\.com\b/',

        // Google OAuth
        'Google OAuth ID' => '/\b[0-9]+-[0-9A-Za-z_]{32}\.apps\.googleusercontent\.com\b/',

        // Network identifiers (optional - can be removed if too aggressive)
        'IPv4 Address' => '/\b(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\b/',
        'MAC Address' => '/\b(?:[a-fA-F0-9]{2}[:\-]){5}[a-fA-F0-9]{2}\b/',

        // Generic patterns for common secret formats
        'Bearer Token' => '/\bBearer\s+[a-zA-Z0-9\-_\.]+\b/i',
        'Basic Auth Header' => '/\bBasic\s+[a-zA-Z0-9+\/=]+\b/i',
        'Password Field' => '/["\']?(?:password|passwd|pwd|secret|token|api_key|apikey|auth)["\']?\s*[=:]\s*["\'][^"\']{4,}["\']/i',
    ];

    /** @var array<int, string> */
    private array $patterns;

    private string $mask;

    /**
     * @param array<int, string>|null $patterns Custom patterns (null = use defaults, empty array = disable masking)
     */
    public function __construct(
        ?array $patterns = null,
        string $mask = self::MASK,
    ) {
        if ($patterns === null) {
            // Use default patterns
            $this->patterns = array_values(self::DEFAULT_PATTERNS);
        } else {
            // Use provided patterns (empty array disables masking)
            $this->patterns = array_values(array_filter($patterns, 'is_string'));
        }

        $this->mask = $mask;
    }

    /**
     * Mask secrets in a string value.
     */
    public function mask(string $value): string
    {
        if ($this->patterns === []) {
            return $value;
        }

        foreach ($this->patterns as $pattern) {
            if (!is_string($pattern) || $pattern === '') {
                continue;
            }

            try {
                $result = @preg_replace($pattern, $this->mask, $value);
                if (is_string($result)) {
                    $value = $result;
                }
            } catch (\Throwable) {
                // Invalid pattern, skip it
            }
        }

        return $value;
    }

    /**
     * Recursively mask secrets in mixed data (arrays, objects converted to arrays, etc.).
     */
    public function maskRecursive(mixed $value): mixed
    {
        if ($this->patterns === []) {
            return $value;
        }

        if (is_string($value)) {
            return $this->mask($value);
        }

        if (is_array($value)) {
            $result = [];
            foreach ($value as $key => $item) {
                $maskedKey = is_string($key) ? $this->mask($key) : $key;
                $result[$maskedKey] = $this->maskRecursive($item);
            }
            return $result;
        }

        // Scalars and other types pass through unchanged
        return $value;
    }

    /**
     * Get the list of pattern names from defaults.
     *
     * @return array<int, string>
     */
    public static function defaultPatternNames(): array
    {
        return array_keys(self::DEFAULT_PATTERNS);
    }

    /**
     * Check if masking is enabled (has patterns).
     */
    public function isEnabled(): bool
    {
        return $this->patterns !== [];
    }
}
