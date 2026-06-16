<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp;

use Mcp\Capability\Discovery\DiscovererInterface;
use Mcp\Capability\Registry\Loader\LoaderInterface;
use Mcp\Capability\RegistryInterface;

final class ProfileDiscoveryLoader implements LoaderInterface
{
    /**
     * @param array<int, string> $scanDirs
     * @param array<int, string> $excludeDirs
     * @param array<int, string> $toolNames
     * @param array<int, string> $resourceUris
     * @param array<int, string> $resourceTemplateUris
     * @param array<int, string> $namePatterns
     */
    public function __construct(
        private readonly string $basePath,
        private readonly array $scanDirs,
        private readonly array $excludeDirs,
        private readonly DiscovererInterface $discoverer,
        private readonly array $toolNames,
        private readonly array $resourceUris,
        private readonly array $resourceTemplateUris,
        private readonly array $namePatterns = DiscovererInterface::DEFAULT_NAME_PATERNS,
    ) {
    }

    public function load(RegistryInterface $registry): void
    {
        $discovered = $this->discoverer->discover(
            $this->basePath,
            $this->scanDirs,
            $this->excludeDirs,
            $this->namePatterns,
        );

        $allowedTools = array_fill_keys($this->toolNames, true);
        foreach ($discovered->getTools() as $name => $reference) {
            if (!isset($allowedTools[$name]) || $registry->hasTool($name)) {
                continue;
            }

            $registry->registerTool($reference->tool, $reference->handler);
        }

        $allowedResources = array_fill_keys($this->resourceUris, true);
        foreach ($discovered->getResources() as $uri => $reference) {
            if (!isset($allowedResources[$uri]) || $registry->hasResource($uri)) {
                continue;
            }

            $registry->registerResource($reference->resource, $reference->handler);
        }

        $allowedResourceTemplates = array_fill_keys($this->resourceTemplateUris, true);
        foreach ($discovered->getResourceTemplates() as $uriTemplate => $reference) {
            if (!isset($allowedResourceTemplates[$uriTemplate]) || $registry->hasResourceTemplate($uriTemplate)) {
                continue;
            }

            $registry->registerResourceTemplate(
                $reference->resourceTemplate,
                $reference->handler,
                $reference->completionProviders,
            );
        }
    }
}
