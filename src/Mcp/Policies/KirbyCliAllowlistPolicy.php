<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Policies;

use Bnomei\KirbyMcp\Mcp\Support\RuntimeCommands;
use Bnomei\KirbyMcp\Project\KirbyMcpConfig;

final class KirbyCliAllowlistPolicy
{
    /**
     * Minimal built-in allowlist for read-only commands.
     *
     * @var array<int, string>
     */
    public const DEFAULT_ALLOW = [
        'help',
        'version',
        'roots',
        'security',
        'license:info',
        'uuid:duplicates',
        RuntimeCommands::RENDER,
    ];

    /**
     * Built-in allowlist for write-capable commands when allowWrite=true.
     *
     * @var array<int, string>
     */
    public const DEFAULT_ALLOW_WRITE = [
        'make:*',
        'clear:*',
    ];

    /**
     * Commands that mutate state and therefore always require allowWrite=true,
     * even when an operator lists them in cli.allow. Keeps the execute-scoped
     * CLI tool from reopening write paths that bypass allowWrite, HTTP write
     * scope, and schema attestation guards.
     *
     * @var array<int, string>
     */
    public const WRITE_CAPABLE = [
        'make:*',
        'clear:*',
        'mcp:*:update',
        RuntimeCommands::UPDATE,
        RuntimeCommands::INSTALL,
    ];

    /** @var array<int, string> */
    private array $deny;

    /** @var array<int, string> */
    private array $allow;

    /** @var array<int, string> */
    private array $allowWrite;

    /**
     * @param array<int, string> $defaultAllow
     * @param array<int, string> $defaultAllowWrite
     */
    public function __construct(
        private readonly KirbyMcpConfig $config,
        array $defaultAllow = self::DEFAULT_ALLOW,
        array $defaultAllowWrite = self::DEFAULT_ALLOW_WRITE,
    ) {
        $this->deny = $this->config->cliDeny();

        $this->allow = array_values(array_unique(array_merge($defaultAllow, $this->config->cliAllow())));
        $this->allowWrite = array_values(array_unique(array_merge($defaultAllowWrite, $this->config->cliAllowWrite())));
    }

    public function evaluate(string $command, bool $allowWrite): KirbyCliAllowlistDecision
    {
        $matchedDeny = $this->firstMatchingPattern($command, $this->deny);
        if ($matchedDeny !== null) {
            return new KirbyCliAllowlistDecision(
                allowed: false,
                matchedDeny: $matchedDeny,
                matchedAllow: null,
                matchedAllowWrite: null,
                matchedWriteCapable: null,
            );
        }

        $matchedAllow = $this->firstMatchingPattern($command, $this->allow);
        $matchedAllowWrite = $this->firstMatchingPattern($command, $this->allowWrite);
        $matchedWriteCapable = $this->firstMatchingPattern($command, self::WRITE_CAPABLE);

        // A command is write-capable when it matches the intrinsic write list or
        // an operator-configured allowWrite pattern. Write-capable commands must
        // be listed (allow or allowWrite) AND invoked with allowWrite=true.
        // Read-only commands keep the original allow/allowWrite semantics.
        if ($matchedWriteCapable !== null || $matchedAllowWrite !== null) {
            $allowed = $allowWrite === true && ($matchedAllow !== null || $matchedAllowWrite !== null);
        } else {
            $allowed = $matchedAllow !== null;
        }

        return new KirbyCliAllowlistDecision(
            allowed: $allowed,
            matchedDeny: null,
            matchedAllow: $matchedAllow,
            matchedAllowWrite: $matchedAllowWrite,
            matchedWriteCapable: $matchedWriteCapable,
        );
    }

    /**
     * Match a command against allow/deny patterns.
     *
     * Supported pattern: `*` wildcard.
     *
     * @param array<int, string> $patterns
     */
    private function firstMatchingPattern(string $command, array $patterns): ?string
    {
        foreach ($patterns as $pattern) {
            $pattern = trim($pattern);
            if ($pattern === '') {
                continue;
            }

            if ($pattern === $command) {
                return $pattern;
            }

            if (str_contains($pattern, '*') === false) {
                continue;
            }

            $regex = '/^' . str_replace('\\*', '.*', preg_quote($pattern, '/')) . '$/u';
            try {
                $result = @preg_match($regex, $command);
                if ($result === 1) {
                    return $pattern;
                }
            } catch (\Throwable) {
                // Invalid regex pattern in config - skip silently
                continue;
            }
        }

        return null;
    }
}
