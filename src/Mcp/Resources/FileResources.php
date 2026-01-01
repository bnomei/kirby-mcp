<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Resources;

use Bnomei\KirbyMcp\Mcp\Attributes\McpToolIndex;
use Bnomei\KirbyMcp\Mcp\ProjectContext;
use Bnomei\KirbyMcp\Mcp\Support\KirbyRuntimeContext;
use Bnomei\KirbyMcp\Mcp\Support\RuntimeCommands;
use Bnomei\KirbyMcp\Mcp\Support\RuntimeCommandRunner;
use Mcp\Capability\Attribute\McpResourceTemplate;

final class FileResources
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
        uriTemplate: 'kirby://file/content/{encodedIdOrUuid}',
        name: 'file_content',
        description: 'Read a file’s content/metadata by id or uuid. The id must be URL-encoded (e.g. about%2Fcover.jpg). UUIDs can be passed as the raw UUID or as a URL-encoded Kirby UUID like file%3A%2F%2F<uuid>.',
        mimeType: 'application/json',
    )]
    #[McpToolIndex(
        whenToUse: 'Use to read file content/metadata by id or uuid via the resource template kirby://file/content/{encodedIdOrUuid}.',
        keywords: [
            'file' => 80,
            'content' => 100,
            'read' => 60,
            'uuid' => 40,
            'metadata' => 30,
            'runtime' => 20,
        ],
    )]
    public function fileContent(string $encodedIdOrUuid): array
    {
        $id = trim(rawurldecode($encodedIdOrUuid));
        if ($id === '') {
            return [
                'ok' => false,
                'message' => 'File id/uuid must not be empty.',
            ];
        }

        $result = $this->runner->runMarkedJson(
            expectedCommandRelativePath: RuntimeCommands::FILE_CONTENT_FILE,
            args: [RuntimeCommands::FILE_CONTENT, $id],
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
