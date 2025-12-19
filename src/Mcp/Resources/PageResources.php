<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Resources;

use Bnomei\KirbyMcp\Mcp\Attributes\McpToolIndex;
use Bnomei\KirbyMcp\Mcp\ProjectContext;
use Bnomei\KirbyMcp\Mcp\Support\KirbyRuntimeContext;
use Bnomei\KirbyMcp\Mcp\Support\RuntimeCommands;
use Bnomei\KirbyMcp\Mcp\Support\RuntimeCommandRunner;
use Mcp\Capability\Attribute\McpResourceTemplate;

final class PageResources
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
    #[McpResourceTemplate(
        uriTemplate: 'kirby://page/content/{encodedIdOrUuid}',
        name: 'page_content',
        description: 'Read a page’s content by id or uuid. The id must be URL-encoded (e.g. home or blog%2Fpost). UUIDs can be passed as the raw UUID (preferred) or as a URL-encoded Kirby UUID like page%3A%2F%2F<uuid>.',
        mimeType: 'application/json',
    )]
    #[McpToolIndex(
        whenToUse: 'Use to read page content by id or uuid via the resource template kirby://page/content/{encodedIdOrUuid}.',
        keywords: [
            'page' => 80,
            'content' => 100,
            'read' => 60,
            'uuid' => 40,
            'fields' => 30,
            'runtime' => 20,
        ],
    )]
    public function pageContent(string $encodedIdOrUuid): array
    {
        $id = trim(rawurldecode($encodedIdOrUuid));
        if ($id === '') {
            return [
                'ok' => false,
                'message' => 'Page id/uuid must not be empty.',
            ];
        }

        $result = $this->runner->runMarkedJson(
            expectedCommandRelativePath: RuntimeCommands::PAGE_CONTENT_FILE,
            args: [RuntimeCommands::PAGE_CONTENT, $id],
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
