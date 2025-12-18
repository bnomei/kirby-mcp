<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Resources;

use Bnomei\KirbyMcp\Cli\KirbyCliRunner;
use Bnomei\KirbyMcp\Cli\McpMarkedJsonExtractor;
use Bnomei\KirbyMcp\Mcp\ProjectContext;
use Bnomei\KirbyMcp\Project\KirbyRootsInspector;
use Mcp\Capability\Attribute\McpResourceTemplate;

final class PageResources
{
    public function __construct(
        private readonly ProjectContext $context = new ProjectContext(),
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    #[McpResourceTemplate(
        uriTemplate: 'kirby://page/content/{encodedIdOrUuid}',
        name: 'page_content',
        description: 'Read a page’s content by id or uuid. The id must be URL-encoded (e.g. home or blog%2Fpost). Requires runtime commands installed.',
        mimeType: 'application/json',
    )]
    public function pageContent(string $encodedIdOrUuid): array
    {
        $projectRoot = $this->context->projectRoot();
        $host = $this->context->kirbyHost();

        $id = trim(rawurldecode($encodedIdOrUuid));
        if ($id === '') {
            return [
                'ok' => false,
                'message' => 'Page id/uuid must not be empty.',
            ];
        }

        $commandsRoot = (new KirbyRootsInspector())->inspect($projectRoot, $host)->commandsRoot()
            ?? rtrim($projectRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'site' . DIRECTORY_SEPARATOR . 'commands';

        $expectedCommandFile = rtrim($commandsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'mcp' . DIRECTORY_SEPARATOR . 'page' . DIRECTORY_SEPARATOR . 'content.php';

        if (!is_file($expectedCommandFile)) {
            return [
                'ok' => false,
                'needsRuntimeInstall' => true,
                'message' => 'Kirby MCP runtime CLI commands are not installed in this project. Run kirby_runtime_install first.',
                'expectedCommandFile' => $expectedCommandFile,
            ];
        }

        $env = [];
        if (is_string($host) && $host !== '') {
            $env['KIRBY_HOST'] = $host;
        }

        $cliResult = (new KirbyCliRunner())->run(
            projectRoot: $projectRoot,
            args: [
                'mcp:page:content',
                $id,
            ],
            env: $env,
            timeoutSeconds: 60,
        );

        try {
            $payload = McpMarkedJsonExtractor::extract($cliResult->stdout);
        } catch (\Throwable $exception) {
            return [
                'ok' => false,
                'parseError' => $exception->getMessage(),
                'cli' => $cliResult->toArray(),
            ];
        }

        if (!is_array($payload)) {
            return [
                'ok' => false,
                'parseError' => 'Unable to parse JSON output from Kirby CLI command.',
                'cli' => $cliResult->toArray(),
            ];
        }

        /** @var array<mixed> $payload */
        return array_merge($payload, [
            'cli' => $cliResult->toArray(),
        ]);
    }
}
