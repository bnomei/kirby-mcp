<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Resources;

use Bnomei\KirbyMcp\Cli\KirbyCliRunner;
use Bnomei\KirbyMcp\Cli\McpMarkedJsonExtractor;
use Bnomei\KirbyMcp\Mcp\Completion\BlueprintIdCompletionProvider;
use Bnomei\KirbyMcp\Mcp\ProjectContext;
use Bnomei\KirbyMcp\Project\KirbyRootsInspector;
use Mcp\Capability\Attribute\CompletionProvider;
use Mcp\Capability\Attribute\McpResourceTemplate;
use Mcp\Exception\ResourceReadException;

final class BlueprintResources
{
    public function __construct(
        private readonly ProjectContext $context = new ProjectContext(),
    ) {
    }

    /**
     * Read a blueprint by id (runtime truth via `mcp:blueprint`).
     *
     * @return array<string, mixed>
     */
    #[McpResourceTemplate(
        uriTemplate: 'kirby://blueprint/{encodedId}',
        name: 'blueprint',
        description: 'Read a blueprint by id via the installed `kirby mcp:blueprint` runtime command (supports plugin extensions). The id must be URL-encoded (e.g. pages%2Fhome). Requires runtime commands installed.',
        mimeType: 'application/json',
    )]
    public function blueprint(
        #[CompletionProvider(provider: BlueprintIdCompletionProvider::class)]
        string $encodedId
    ): array {
        $projectRoot = $this->context->projectRoot();
        $host = $this->context->kirbyHost();
        $blueprintsRoot = $this->resolveBlueprintsRoot($projectRoot, $host);

        $decoded = rawurldecode($encodedId);
        $id = trim($decoded);
        if ($id === '') {
            throw new ResourceReadException('Blueprint id must not be empty.');
        }

        $commandsRoot = (new KirbyRootsInspector())->inspect($projectRoot, $host)->commandsRoot()
            ?? rtrim($projectRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'site' . DIRECTORY_SEPARATOR . 'commands';
        $expectedCommandFile = rtrim($commandsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'mcp' . DIRECTORY_SEPARATOR . 'blueprint.php';

        if (!is_file($expectedCommandFile)) {
            return [
                'ok' => false,
                'needsRuntimeInstall' => true,
                'message' => 'Kirby MCP runtime CLI commands are not installed in this project. Run kirby_runtime_install first.',
                'expectedCommandFile' => $expectedCommandFile,
            ];
        }

        $env = [];
        if (is_string($host) && trim($host) !== '') {
            $env['KIRBY_HOST'] = trim($host);
        }

        $cliResult = (new KirbyCliRunner())->run(
            projectRoot: $projectRoot,
            args: ['mcp:blueprint', $id],
            env: $env,
            timeoutSeconds: 60,
        );

        $payload = McpMarkedJsonExtractor::extract($cliResult->stdout);
        if (!is_array($payload)) {
            return [
                'ok' => false,
                'mode' => 'runtime',
                'projectRoot' => $projectRoot,
                'host' => $host,
                'blueprintsRoot' => $blueprintsRoot,
                'id' => $id,
                'parseError' => 'Unable to parse JSON output from Kirby CLI command.',
                'cliMeta' => [
                    'exitCode' => $cliResult->exitCode,
                    'timedOut' => $cliResult->timedOut,
                ],
                'message' => 'Use the `kirby_blueprint_read` tool with debug=true to include CLI stdout/stderr.',
            ];
        }

        return array_merge($payload, [
            'mode' => 'runtime',
            'projectRoot' => $projectRoot,
            'host' => $host,
            'blueprintsRoot' => $blueprintsRoot,
            'cliMeta' => [
                'exitCode' => $cliResult->exitCode,
                'timedOut' => $cliResult->timedOut,
            ],
        ]);
    }

    private function resolveBlueprintsRoot(string $projectRoot, ?string $host = null): string
    {
        $roots = (new KirbyRootsInspector())->inspect($projectRoot, $host);
        $blueprintsRoot = $roots->get('blueprints');

        return is_string($blueprintsRoot) && $blueprintsRoot !== ''
            ? rtrim($blueprintsRoot, DIRECTORY_SEPARATOR)
            : rtrim($projectRoot, DIRECTORY_SEPARATOR) . '/site/blueprints';
    }
}
