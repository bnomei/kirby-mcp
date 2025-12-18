<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Completion;

use Bnomei\KirbyMcp\Blueprint\BlueprintScanner;
use Bnomei\KirbyMcp\Cli\KirbyCliRunner;
use Bnomei\KirbyMcp\Cli\McpMarkedJsonExtractor;
use Bnomei\KirbyMcp\Mcp\ProjectContext;
use Bnomei\KirbyMcp\Project\KirbyRootsInspector;
use Mcp\Capability\Completion\ProviderInterface;

final class BlueprintIdCompletionProvider implements ProviderInterface
{
    public function getCompletions(string $currentValue): array
    {
        $currentValue = trim($currentValue);

        $context = new ProjectContext();
        $projectRoot = $context->projectRoot();
        $host = $context->kirbyHost();

        $cacheKey = rtrim($projectRoot, DIRECTORY_SEPARATOR) . '|' . trim((string) $host);
        static $cachedIdsByProject = [];

        if (!isset($cachedIdsByProject[$cacheKey])) {
            $roots = (new KirbyRootsInspector())->inspect($projectRoot, $host);
            $blueprintsRoot = $roots->get('blueprints') ?? ($projectRoot . '/site/blueprints');

            $commandsRoot = $roots->commandsRoot()
                ?? rtrim($projectRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'site' . DIRECTORY_SEPARATOR . 'commands';
            $expectedCommandFile = rtrim($commandsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'mcp' . DIRECTORY_SEPARATOR . 'blueprints.php';

            $ids = [];

            if (is_file($expectedCommandFile)) {
                $env = [];
                if (is_string($host) && trim($host) !== '') {
                    $env['KIRBY_HOST'] = trim($host);
                }

                $cliResult = (new KirbyCliRunner())->run(
                    projectRoot: $projectRoot,
                    args: ['mcp:blueprints', '--ids-only'],
                    env: $env,
                    timeoutSeconds: 60,
                );

                $payload = McpMarkedJsonExtractor::extract($cliResult->stdout);
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
            }

            if ($ids === []) {
                $scan = (new BlueprintScanner())->scan($projectRoot, $blueprintsRoot);
                $ids = array_keys($scan->blueprints);
            }

            $ids = array_values(array_unique($ids));
            sort($ids);

            $cachedIdsByProject[$cacheKey] = $ids;
        }

        /** @var array<int, string> $ids */
        $ids = $cachedIdsByProject[$cacheKey];

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
