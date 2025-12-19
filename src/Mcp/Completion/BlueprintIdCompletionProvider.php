<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Completion;

use Bnomei\KirbyMcp\Blueprint\BlueprintScanner;
use Bnomei\KirbyMcp\Mcp\ProjectContext;
use Bnomei\KirbyMcp\Mcp\Support\KirbyRuntimeContext;
use Bnomei\KirbyMcp\Mcp\Support\RuntimeCommands;
use Bnomei\KirbyMcp\Mcp\Support\RuntimeCommandRunner;
use Bnomei\KirbyMcp\Project\KirbyMcpConfig;
use Bnomei\KirbyMcp\Support\StaticCache;
use Mcp\Capability\Completion\ProviderInterface;

final class BlueprintIdCompletionProvider implements ProviderInterface
{
    public function getCompletions(string $currentValue): array
    {
        $currentValue = trim($currentValue);

        $runtime = new KirbyRuntimeContext(new ProjectContext());
        $projectRoot = $runtime->projectRoot();
        $host = $runtime->host();

        $ttlSeconds = KirbyMcpConfig::load($projectRoot)->cacheTtlSeconds();
        $cacheKey = 'completion:blueprints:' . sha1(rtrim($projectRoot, DIRECTORY_SEPARATOR) . '|' . trim((string) $host));

        $ids = null;
        if ($ttlSeconds > 0) {
            $cached = StaticCache::get($cacheKey);
            if (is_array($cached)) {
                $ids = array_values(array_filter($cached, static fn (mixed $id): bool => is_string($id) && $id !== ''));
            }
        }

        if (!is_array($ids)) {
            $blueprintsRoot = $runtime->root(
                key: 'blueprints',
                fallback: rtrim($projectRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'site' . DIRECTORY_SEPARATOR . 'blueprints',
            );

            $ids = [];

            $result = (new RuntimeCommandRunner($runtime))->runMarkedJson(
                expectedCommandRelativePath: RuntimeCommands::BLUEPRINTS_FILE,
                args: [RuntimeCommands::BLUEPRINTS, '--ids-only'],
                timeoutSeconds: 60,
            );

            $payload = $result->payload;
            if (is_array($payload) && is_array($payload['blueprints'] ?? null)) {
                foreach ($payload['blueprints'] as $entry) {
                    if (!is_array($entry)) {
                        continue;
                    }

                    $id = $entry['id'] ?? null;
                    if (!is_string($id) || $id === '') {
                        continue;
                    }

                    $ids[] = $id;
                }
            }

            if ($ids === []) {
                $scan = (new BlueprintScanner())->scan($projectRoot, $blueprintsRoot);
                $ids = array_keys($scan->blueprints);
            }

            $ids = array_values(array_unique($ids));
            sort($ids);

            if ($ttlSeconds > 0) {
                StaticCache::set($cacheKey, $ids, $ttlSeconds);
            }
        }

        $encodedIds = [];
        foreach ($ids as $id) {
            $encoded = rawurlencode($id);
            if ($encoded === '') {
                continue;
            }

            if ($currentValue !== '') {
                $isEncodedQuery = str_contains($currentValue, '%');
                $matches = $isEncodedQuery
                    ? str_contains(strtolower($encoded), strtolower($currentValue))
                    : (str_contains(strtolower($id), strtolower($currentValue)) || str_contains(strtolower($encoded), strtolower($currentValue)));

                if (!$matches) {
                    continue;
                }
            }

            $encodedIds[] = $encoded;
        }

        $encodedIds = array_values(array_unique($encodedIds));
        sort($encodedIds);

        return $encodedIds;
    }
}
