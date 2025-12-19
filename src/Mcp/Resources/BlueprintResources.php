<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Resources;

use Bnomei\KirbyMcp\Mcp\Completion\BlueprintIdCompletionProvider;
use Bnomei\KirbyMcp\Mcp\Attributes\McpToolIndex;
use Bnomei\KirbyMcp\Mcp\ProjectContext;
use Bnomei\KirbyMcp\Mcp\Support\KirbyRuntimeContext;
use Bnomei\KirbyMcp\Mcp\Support\RuntimeCommands;
use Bnomei\KirbyMcp\Mcp\Support\RuntimeCommandRunner;
use Mcp\Capability\Attribute\CompletionProvider;
use Mcp\Capability\Attribute\McpResourceTemplate;
use Mcp\Exception\ResourceReadException;

final class BlueprintResources
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
     * Read a blueprint by id (runtime truth via `mcp:blueprint`).
     *
     * @return array<string, mixed>
     */
    #[McpResourceTemplate(
        uriTemplate: 'kirby://blueprint/{encodedId}',
        name: 'blueprint',
        description: 'Read a blueprint by id via the installed `kirby mcp:blueprint` runtime command (supports plugin extensions). The id must be URL-encoded (e.g. pages%2Fhome).',
        mimeType: 'application/json',
    )]
    #[McpToolIndex(
        whenToUse: 'Use to read a single Kirby blueprint (schema) by id via the resource template kirby://blueprint/{encodedId}.',
        keywords: [
            'blueprint' => 100,
            'schema' => 60,
            'fields' => 50,
            'tabs' => 40,
            'sections' => 40,
            'validation' => 30,
            'runtime' => 20,
        ],
    )]
    public function blueprint(
        #[CompletionProvider(provider: BlueprintIdCompletionProvider::class)]
        string $encodedId
    ): array {
        $projectRoot = $this->runtime->projectRoot();
        $host = $this->runtime->host();
        $blueprintsRoot = $this->runtime->root(
            key: 'blueprints',
            fallback: rtrim($projectRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'site' . DIRECTORY_SEPARATOR . 'blueprints',
        );

        $decoded = rawurldecode($encodedId);
        $id = trim($decoded);
        if ($id === '') {
            throw new ResourceReadException('Blueprint id must not be empty.');
        }

        $result = $this->runner->runMarkedJson(
            expectedCommandRelativePath: RuntimeCommands::BLUEPRINT_FILE,
            args: [RuntimeCommands::BLUEPRINT, $id],
            timeoutSeconds: 60,
        );

        if ($result->installed === false) {
            return $result->needsRuntimeInstallResponse();
        }

        $payload = $result->payload;
        if (!is_array($payload)) {
            return $result->parseErrorResponse([
                'mode' => 'runtime',
                'projectRoot' => $projectRoot,
                'host' => $host,
                'blueprintsRoot' => $blueprintsRoot,
                'id' => $id,
                'cliMeta' => $result->cliMeta(),
                'message' => 'Use the `kirby_blueprint_read` tool with debug=true to include CLI stdout/stderr.',
            ]);
        }

        return array_merge($payload, [
            'mode' => 'runtime',
            'projectRoot' => $projectRoot,
            'host' => $host,
            'blueprintsRoot' => $blueprintsRoot,
            'cliMeta' => $result->cliMeta(),
        ]);
    }
}
