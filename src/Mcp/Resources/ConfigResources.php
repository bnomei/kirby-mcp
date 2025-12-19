<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Resources;

use Bnomei\KirbyMcp\Mcp\Attributes\McpToolIndex;
use Bnomei\KirbyMcp\Mcp\ProjectContext;
use Bnomei\KirbyMcp\Mcp\Support\KirbyRuntimeContext;
use Bnomei\KirbyMcp\Mcp\Support\RuntimeCommands;
use Bnomei\KirbyMcp\Mcp\Support\RuntimeCommandRunner;
use Mcp\Capability\Attribute\McpResourceTemplate;
use Mcp\Exception\ResourceReadException;

final class ConfigResources
{
    private readonly KirbyRuntimeContext $runtime;
    private readonly RuntimeCommandRunner $runner;

    public function __construct(
        private readonly ProjectContext $context = new ProjectContext(),
    ) {
        $this->runtime = new KirbyRuntimeContext($this->context);
        $this->runner = new RuntimeCommandRunner($this->runtime);
    }

    #[McpResourceTemplate(
        uriTemplate: 'kirby://config/{option}',
        name: 'config_get',
        description: 'Read a Kirby config option by dot notation (or JSON-encoded array of path segments) via the installed `kirby mcp:config:get` runtime command.',
        mimeType: 'text/plain',
    )]
    #[McpToolIndex(
        whenToUse: 'Use to read a Kirby config option via the resource template kirby://config/{option} (dot path).',
        keywords: [
            'config' => 100,
            'option' => 60,
            'settings' => 40,
            'read' => 40,
            'runtime' => 20,
        ],
    )]
    public function configGet(string $option): string
    {
        $option = trim(rawurldecode($option));
        if ($option === '') {
            throw new ResourceReadException('Option path must not be empty.');
        }

        $result = $this->runner->runMarkedJson(
            expectedCommandRelativePath: RuntimeCommands::CONFIG_GET_FILE,
            args: [RuntimeCommands::CONFIG_GET, $option],
            timeoutSeconds: 30,
        );

        if ($result->installed === false) {
            return RuntimeCommandRunner::NEEDS_RUNTIME_INSTALL_MESSAGE;
        }

        if (!is_array($result->payload)) {
            if (is_string($result->parseError) && $result->parseError !== '') {
                if ($result->parseError === RuntimeCommandRunner::DEFAULT_PARSE_ERROR) {
                    return RuntimeCommandRunner::DEFAULT_PARSE_ERROR;
                }

                return 'Unable to parse JSON output from Kirby CLI command: ' . $result->parseError;
            }

            return RuntimeCommandRunner::DEFAULT_PARSE_ERROR;
        }

        $payload = $result->payload;

        $line = $payload['line'] ?? null;
        if (is_string($line) && trim($line) !== '') {
            return $line;
        }

        $message = $payload['error']['message'] ?? null;
        if (is_string($message) && trim($message) !== '') {
            return trim($message);
        }

        return 'Kirby CLI command returned unexpected output.';
    }
}
