<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Resources;

use Bnomei\KirbyMcp\Mcp\Attributes\McpToolIndex;
use Bnomei\KirbyMcp\Mcp\ProjectContext;
use Bnomei\KirbyMcp\Mcp\Support\KirbyRuntimeContext;
use Bnomei\KirbyMcp\Mcp\Support\RuntimeCommands;
use Bnomei\KirbyMcp\Mcp\Support\RuntimeCommandRunner;
use Mcp\Capability\Attribute\McpResource;

final class SiteResources
{
    private readonly KirbyRuntimeContext $runtime;
    private readonly RuntimeCommandRunner $runner;

    public function __construct(
        private readonly ProjectContext $context = new ProjectContext(),
    ) {
        $this->runtime = new KirbyRuntimeContext($this->context);
        $this->runner = new RuntimeCommandRunner($this->runtime);
    }

    /**
     * @return array<string, mixed>
     */
    #[McpResource(
        uri: 'kirby://site/content',
        name: 'site_content',
        description: 'Read the site’s content (current version) via the runtime CLI command.',
        mimeType: 'application/json',
    )]
    #[McpToolIndex(
        whenToUse: 'Use to read site content via the resource kirby://site/content.',
        keywords: [
            'site' => 80,
            'content' => 100,
            'read' => 60,
            'fields' => 30,
            'runtime' => 20,
        ],
    )]
    public function siteContent(): array
    {
        $result = $this->runner->runMarkedJson(
            expectedCommandRelativePath: RuntimeCommands::SITE_CONTENT_FILE,
            args: [RuntimeCommands::SITE_CONTENT],
            timeoutSeconds: 60,
        );

        if ($result->installed === false) {
            return $result->needsRuntimeInstallResponse();
        }

        if (!is_array($result->payload)) {
            return $result->parseErrorResponse([
                'cli' => $result->cli(),
            ]);
        }

        return array_merge($result->payload, [
            'cli' => $result->cli(),
        ]);
    }
}
