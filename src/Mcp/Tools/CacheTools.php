<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Tools;

use Bnomei\KirbyMcp\Mcp\Attributes\McpToolIndex;
use Bnomei\KirbyMcp\Mcp\Support\KirbyRuntimeContext;
use Bnomei\KirbyMcp\Mcp\ToolIndex;
use Bnomei\KirbyMcp\Project\ComposerInspector;
use Bnomei\KirbyMcp\Project\KirbyMcpConfig;
use Bnomei\KirbyMcp\Mcp\Tools\Concerns\StructuredToolResult;
use Bnomei\KirbyMcp\Support\StaticCache;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\RequestContext;

final class CacheTools
{
    use StructuredToolResult;

    /**
     * Clear in-memory caches for the current MCP process.
     *
     * @return array{
     *   ok: bool,
     *   scope: string,
     *   staticCache: array{removed:int|null, prefix:string|null},
     *   composerCache: array{removed:int|null},
     *   configCache: array{removed:int|null},
     *   rootsCache: array{removed:int|null},
     *   toolIndex: array{cleared:bool|null},
     *   message: string|null
     * }
     */
    #[McpToolIndex(
        whenToUse: 'Use to clear this MCP server’s in-memory caches (useful after changing cache settings, installing runtime commands, or debugging stale context).',
        keywords: [
            'cache' => 100,
            'clear' => 80,
            'reset' => 60,
            'stale' => 40,
            'refresh' => 40,
            'commands' => 20,
            'roots' => 20,
        ],
    )]
    #[McpTool(
        name: 'kirby_cache_clear',
        description: 'Clear in-memory caches for the current MCP process (StaticCache, config cache, composer cache, roots cache, tool index). This does not delete any project files.',
        annotations: new ToolAnnotations(
            title: 'Clear Cache',
            readOnlyHint: true,
            destructiveHint: false,
            idempotentHint: true,
            openWorldHint: false,
        ),
    )]
    public function clearCache(string $scope = 'all', ?string $prefix = null, ?RequestContext $context = null): array|CallToolResult
    {
        $scope = trim(strtolower($scope));
        $prefix = is_string($prefix) ? trim($prefix) : null;

        $staticRemoved = null;
        $staticPrefix = null;
        $composerRemoved = null;
        $configRemoved = null;
        $rootsRemoved = null;
        $toolIndexCleared = null;

        $allowed = ['all', 'static', 'cli', 'roots', 'tools', 'prefix', 'config', 'composer'];
        if (!in_array($scope, $allowed, true)) {
            return $this->maybeStructuredResult($context, [
                'ok' => false,
                'scope' => $scope,
                'staticCache' => ['removed' => null, 'prefix' => null],
                'composerCache' => ['removed' => null],
                'configCache' => ['removed' => null],
                'rootsCache' => ['removed' => null],
                'toolIndex' => ['cleared' => null],
                'message' => 'Invalid scope. Allowed: ' . implode(', ', $allowed) . '.',
            ]);
        }

        if ($scope === 'prefix') {
            if (!is_string($prefix) || $prefix === '') {
                return $this->maybeStructuredResult($context, [
                    'ok' => false,
                    'scope' => $scope,
                    'staticCache' => ['removed' => null, 'prefix' => null],
                    'composerCache' => ['removed' => null],
                    'configCache' => ['removed' => null],
                    'rootsCache' => ['removed' => null],
                    'toolIndex' => ['cleared' => null],
                    'message' => 'prefix is required when scope=prefix.',
                ]);
            }
        }

        if ($scope === 'all') {
            $staticRemoved = StaticCache::clearPrefix('');
            $staticPrefix = '';
            $composerRemoved = ComposerInspector::clearCache();
            $configRemoved = KirbyMcpConfig::clearCache();
            $rootsRemoved = KirbyRuntimeContext::clearRootsCache();
            ToolIndex::clearCache();
            $toolIndexCleared = true;
        } elseif ($scope === 'static') {
            $staticPrefix = $prefix ?? '';
            $staticRemoved = StaticCache::clearPrefix($staticPrefix);
        } elseif ($scope === 'cli') {
            $staticPrefix = 'cli:';
            $staticRemoved = StaticCache::clearPrefix($staticPrefix);
        } elseif ($scope === 'composer') {
            $composerRemoved = ComposerInspector::clearCache();
        } elseif ($scope === 'config') {
            $configRemoved = KirbyMcpConfig::clearCache();
        } elseif ($scope === 'roots') {
            $rootsRemoved = KirbyRuntimeContext::clearRootsCache();
        } elseif ($scope === 'tools') {
            ToolIndex::clearCache();
            $toolIndexCleared = true;
        } elseif ($scope === 'prefix') {
            // $prefix is guaranteed to be a non-empty string here (validated above)
            $staticPrefix = $prefix ?? '';
            $staticRemoved = StaticCache::clearPrefix($staticPrefix);
        }

        return $this->maybeStructuredResult($context, [
            'ok' => true,
            'scope' => $scope,
            'staticCache' => [
                'removed' => $staticRemoved,
                'prefix' => $staticPrefix,
            ],
            'composerCache' => [
                'removed' => $composerRemoved,
            ],
            'configCache' => [
                'removed' => $configRemoved,
            ],
            'rootsCache' => [
                'removed' => $rootsRemoved,
            ],
            'toolIndex' => [
                'cleared' => $toolIndexCleared,
            ],
            'message' => null,
        ]);
    }
}
