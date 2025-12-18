<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Tools;

use Bnomei\KirbyMcp\Mcp\Attributes\McpToolIndex;
use Bnomei\KirbyMcp\Mcp\McpLog;
use Bnomei\KirbyMcp\Mcp\ProjectContext;
use Bnomei\KirbyMcp\Mcp\SessionState;
use Bnomei\KirbyMcp\Project\ComposerInspector;
use Bnomei\KirbyMcp\Project\EnvironmentDetector;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\ClientGateway;

final class SessionTools
{
    public function __construct(
        private readonly ProjectContext $context = new ProjectContext(),
    ) {
    }

    /**
     * @return array{
     *   initialized: bool,
     *   projectRoot: string,
     *   message: string,
     *   instructions: string,
     *   recommendedNextTools: array<int, string>,
     *   environment: array{projectRoot:string, localRunner:string, signals: array<string, string>},
     *   composer: array{
     *     projectRoot: string,
     *     composerJson: array<mixed>,
     *     composerLock: array<mixed>|null,
     *     scripts: array<string, mixed>,
     *     tools: array<string, mixed>
     *   }
     * }
     */
    #[McpToolIndex(
        whenToUse: 'Call once at the start of a session to load Kirby-specific working guidelines and a quick project audit (composer + environment).',
        keywords: [
            'init' => 100,
            'initialize' => 80,
            'session' => 60,
            'setup' => 60,
            'start' => 40,
            'instructions' => 40,
            'guidelines' => 40,
            'workflow' => 20,
            'mcp' => 20,
        ],
    )]
    #[McpTool(
        name: 'kirby_init',
        description: 'Return Kirby MCP session guidance + project-specific audit (composer + environment). Call this once per session before using other Kirby tools.',
        annotations: new ToolAnnotations(
            title: 'Kirby Init',
            readOnlyHint: true,
            openWorldHint: false,
        ),
    )]
    public function init(?ClientGateway $client = null): array
    {
        SessionState::markInitCalled();

        try {
            $projectRoot = $this->context->projectRoot();

            $composerAudit = (new ComposerInspector())->inspect($projectRoot);
            if (!isset($composerAudit->composerJson['require']['getkirby/cms'])) {
                throw new ToolCallException('This MCP server supports composer-based Kirby installs only (missing getkirby/cms).');
            }

            $environment = (new EnvironmentDetector())->detect($projectRoot);

            $instructions = <<<'TEXT'
Kirby MCP initialization (CLI-first)

Hard constraints:
- Composer-based Kirby installs only (must have `composer.json` and `getkirby/cms`).
- Prefer Kirby CLI as the “runtime truth”. Avoid bootstrapping Kirby manually in the MCP where possible.
- Blueprints are the “schema/models” of Kirby: treat them as a primary source of truth for types/scaffolding.
- Blueprint schema validation is advisory-only (real projects use `extends` and dynamic patterns).

Recommended workflow per session:
1) Call `kirby_project_info` to confirm Kirby + CLI versions and detect local runner (Herd/DDEV/Docker).
2) Call `kirby_composer_audit` to learn how THIS project runs tests/analysis/formatting (use composer scripts when available).
3) Call `kirby_roots` to discover the project’s resolved Kirby directories (projects can override folder locations).
4) Call `kirby_blueprints_index` to understand available blueprints and start mapping blueprint ↔ template ↔ model.

CLI-first guidance:
- Prefer `vendor/bin/kirby` for scaffolding and introspection (`make:*`, `roots`, `security`, `uuid:*`, …).
- Use `kirby_runtime_install` to install Kirby MCP runtime CLI commands into the project (`site/commands/mcp/*`).
  - `kirby_render_page` uses the installed `kirby mcp:render` command to render a page and capture output/errors.
- For deeper structured JSON introspection, plan to add a Kirby plugin with `mcp:*` CLI commands later.

Quality tooling:
- Before generating non-trivial changes, run the project’s test/static-analysis commands discovered by `kirby_composer_audit`.

Docs-first guidance:
- Prefer looking up Kirby docs (or local `knowledge/`) before implementing unfamiliar APIs/options.
TEXT;

            return [
                'initialized' => true,
                'projectRoot' => $projectRoot,
                'message' => 'Initialization complete.',
                'instructions' => $instructions,
                'recommendedNextTools' => [
                    'kirby_project_info',
                    'kirby_composer_audit',
                    'kirby_roots',
                    'kirby_blueprints_index',
                ],
                'environment' => $environment->toArray(),
                'composer' => $composerAudit->toArray(),
            ];
        } catch (ToolCallException $exception) {
            McpLog::error($client, [
                'tool' => 'kirby_init',
                'error' => $exception->getMessage(),
            ]);
            throw $exception;
        } catch (\Throwable $exception) {
            McpLog::error($client, [
                'tool' => 'kirby_init',
                'error' => $exception->getMessage(),
                'exception' => $exception::class,
            ]);
            throw new ToolCallException($exception->getMessage());
        }
    }
}
