<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Tools;

use Bnomei\KirbyMcp\Cli\KirbyCliRunner;
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
    public static function initRequiredMessage(?string $requestedTool = null): string
    {
        $requestedTool = is_string($requestedTool) ? trim($requestedTool) : null;

        $retry = is_string($requestedTool) && $requestedTool !== ''
            ? ' Then retry `' . $requestedTool . '`.'
            : '';

        return 'Call `kirby_init` once per session before using other Kirby MCP tools.' . $retry;
    }

    public function __construct(
        private readonly ProjectContext $context = new ProjectContext(),
    ) {
    }

    /**
     * @return string
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
    public function init(?ClientGateway $client = null): string
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
Kirby MCP initialization (tool-first)

Hard constraints:
- Composer-based Kirby installs only (must have `composer.json` and `getkirby/cms`).
- This MCP server uses Kirby CLI as the “runtime truth” under the hood; prefer Kirby MCP tools/resources over manual bootstrapping or guessing.
- Blueprints are the “schema/models” of Kirby: treat them as a primary source of truth for types/IDE helpers/indexing.
- Blueprint schema validation is advisory-only (real projects use `extends` and dynamic patterns).

Tool/resource-first guidance:
- Interact with the project through Kirby MCP tools. Do not ask the user to run `vendor/bin/kirby` directly.
- Prefer MCP resources for read-only context (project info/roots/composer, tool index, blueprints, page content) when your client supports them.
- IMPORTANT: Immediately after `kirby_init`, discover available MCP resources and resource templates by calling `list_mcp_resources` and `list_mcp_resource_templates` next.
- Use `kirby://...` resources and resource templates first; only fall back to `kirby_search`, `kirby_online` or the open web when a relevant MCP resource/template is missing.
- Panel reference resources (fields/sections): `kirby://fields`, `kirby://field/{type}`, `kirby://sections`, `kirby://section/{type}` (example: `kirby://field/text`).
- If the request involves page “render/rendering” or page “content”, prefer the dedicated tools (`kirby_render_page`, `kirby_read_page_content`, `kirby_update_page_content`) instead of guessing from templates/content files.
- If the request involves Kirby config values/options, prefer the config resource (`kirby://config/{option}`) instead of calling `kirby_run_cli_command` with `mcp:config:get`.
- If the request involves IDE/DX or “types”, call `kirby_ide_helpers_status` to check missing template/snippet PHPDoc `@var` hints and whether any helper files look stale.
- If IDE helper files are stale, regenerate via `kirby_generate_ide_helpers` (writes optional/regeneratable files into `.kirby-mcp/`).
- If you’re unsure what to call next, use `kirby_tool_suggest` (or read `kirby://tools`).

Runtime commands:
- Some tools/resources require project-local Kirby MCP runtime CLI commands. If a response indicates `needsRuntimeInstall`, call `kirby_runtime_install` first.

Prompts:
- Use MCP prompts (e.g. `kirby_project_tour`, `kirby_performance_audit`) when you want a structured, repeatable workflow.
- If your client doesn't support MCP prompts yet, use the fallback resources: `kirby://prompts` and `kirby://prompt/{name}` (e.g. `kirby://prompt/kirby_project_tour`).

Quality tooling:
- Before generating non-trivial changes, run the project’s test/static-analysis/formatting commands discovered by `kirby_composer_audit`.

KB-first guidance:
- For quick “what to do next” MCP guidance and Kirby terminology, prefer the bundled knowledge base + glossary first:
  - `kirby_search` (bundled markdown under `kb/`)
  - Glossary resources: `kirby://glossary` and `kirby://glossary/{term}`
- Use `kirby_online` (official Kirby docs) only when the KB/glossary didn’t provide enough context.
TEXT;

            $kirbyVersionError = null;
            $kirbyVersion = null;
            try {
                $cliResult = (new KirbyCliRunner())->run(
                    projectRoot: $projectRoot,
                    args: ['version'],
                    timeoutSeconds: 30,
                );
                $kirbyVersion = trim($cliResult->stdout);
                if ($kirbyVersion === '') {
                    $kirbyVersion = null;
                }
                if ($cliResult->exitCode !== 0) {
                    $kirbyVersionError = trim($cliResult->stderr) !== '' ? trim($cliResult->stderr) : 'Kirby CLI returned a non-zero exit code.';
                }
            } catch (\Throwable $exception) {
                $kirbyVersionError = $exception->getMessage();
            }

            $projectInfo = [
                'projectRoot' => $projectRoot,
                'phpVersion' => PHP_VERSION,
                'kirbyVersion' => $kirbyVersion,
                'environment' => $environment->toArray(),
                'composer' => $composerAudit->toArray(),
            ];
            if (is_string($kirbyVersionError) && $kirbyVersionError !== '') {
                $projectInfo['kirbyVersionError'] = $kirbyVersionError;
            }

            $toPrettyJson = static function (mixed $value): string {
                try {
                    $json = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
                    return is_string($json) ? $json : 'null';
                } catch (\Throwable $exception) {
                    return json_encode(['error' => $exception->getMessage()]);
                }
            };

            $markdown = $instructions
                . "\n\n## Project Root\n"
                . '`' . $projectRoot . '`'
                . "\n\n## Environment\n```json\n"
                . $toPrettyJson($environment->toArray())
                . "\n```\n\n## Composer Audit\n```json\n"
                . $toPrettyJson($composerAudit->toArray())
                . "\n```\n\n## Project Info\n```json\n"
                . $toPrettyJson($projectInfo)
                . "\n```\n";

            try {
                $ideStatus = (new IdeTools($this->context))->ideHelpersStatus(
                    client: $client,
                    withDetails: false,
                    limit: 10,
                );
                $markdown .= "\n\n## IDE Helpers Status\n```json\n"
                    . $toPrettyJson($ideStatus)
                    . "\n```\n";
            } catch (\Throwable $exception) {
                $markdown .= "\n\n## IDE Helpers Status\n```json\n"
                    . $toPrettyJson(['error' => $exception->getMessage()])
                    . "\n```\n";
            }

            return "<Kirby>\n" . $markdown . "</Kirby>";
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
