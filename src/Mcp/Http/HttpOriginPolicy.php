<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Http;

final readonly class HttpOriginPolicy
{
    /**
     * @param list<string> $allowedOrigins
     */
    public function __construct(
        private array $allowedOrigins = [],
    ) {
    }

    public function allows(?string $origin): bool
    {
        $origin = is_string($origin) ? trim($origin) : '';
        if ($origin === '') {
            return true;
        }

        $allowedOrigins = $this->normalizedAllowedOrigins();
        if ($allowedOrigins !== []) {
            return in_array($origin, $allowedOrigins, true);
        }

        return $this->isLoopbackOrigin($origin);
    }

    /**
     * @return list<string>
     */
    private function normalizedAllowedOrigins(): array
    {
        $allowed = [];
        foreach ($this->allowedOrigins as $origin) {
            $origin = trim($origin);
            if ($origin === '' || $origin === '*') {
                continue;
            }

            $allowed[] = $origin;
        }

        return array_values(array_unique($allowed));
    }

    private function isLoopbackOrigin(string $origin): bool
    {
        $host = parse_url($origin, PHP_URL_HOST);
        if (!is_string($host)) {
            return false;
        }

        $host = strtolower(trim($host, '[]'));

        if ($host === 'localhost' || $host === '::1') {
            return true;
        }

        // Must be a real loopback IP, not a hostname that merely begins with
        // "127." — e.g. `127.0.0.1.evil.com` is an attacker-controlled DNS name
        // (DNS-rebinding), not a loopback address. Require a valid IPv4 literal
        // in the 127.0.0.0/8 range.
        return filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false
            && str_starts_with($host, '127.');
    }
}
