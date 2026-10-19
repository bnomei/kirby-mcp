<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp;

use Bnomei\KirbyMcp\Mcp\Tools\CliTools;
use Bnomei\KirbyMcp\Project\KirbyMcpConfig;
use Mcp\Capability\Registry;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\Request\CallToolRequest;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Schema\Tool;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\RequestContext;

final readonly class CustomTools
{
    /** @param list<string> $arguments */
    private function __construct(
        public Tool $tool,
        private string $command,
        private array $arguments,
        private bool $readOnly,
        private int $timeoutSeconds,
    ) {
    }

    /** @return list<self> */
    public static function configured(): array
    {
        $config = KirbyMcpConfig::load((new ProjectContext())->projectRoot());
        if ($config->error !== null) {
            throw new \InvalidArgumentException($config->error);
        }
        $definitions = $config->data['customTools'] ?? [];
        if (!is_array($definitions) || ($definitions !== [] && array_is_list($definitions))) {
            throw new \InvalidArgumentException('customTools must be an object keyed by tool name.');
        }

        $tools = [];
        foreach ($definitions as $name => $definition) {
            if (!is_string($name) || preg_match('/^custom_[a-zA-Z0-9_.-]{1,57}$/D', $name) !== 1 || !is_array($definition)) {
                throw new \InvalidArgumentException('Custom tool names must start with custom_ and contain at most 64 letters, digits, underscores, dots or hyphens.');
            }
            $fail = static fn (string $message) => new \InvalidArgumentException("customTools.{$name}: {$message}");
            $command = $definition['command'] ?? null;
            if (!is_string($command) || preg_match('/^[a-zA-Z0-9][a-zA-Z0-9:_-]*$/D', $command) !== 1 || str_starts_with(strtolower($command), 'mcp:')) {
                throw $fail('command must be a fixed Kirby CLI command, not an mcp:* runtime command.');
            }
            $description = $definition['description'] ?? null;
            $title = $definition['title'] ?? $name;
            if (!is_string($description) || trim($description) === '' || !is_string($title) || trim($title) === '') {
                throw $fail('description and title must be non-empty strings.');
            }
            $readOnly = $definition['readOnly'] ?? false;
            $timeout = $definition['timeoutSeconds'] ?? 60;
            if (!is_bool($readOnly) || !is_int($timeout) || $timeout < 5 || $timeout > 300) {
                throw $fail('readOnly must be boolean and timeoutSeconds must be an integer from 5 to 300.');
            }
            $arguments = $definition['arguments'] ?? [];
            if (!is_array($arguments) || !array_is_list($arguments)) {
                throw $fail('arguments must be a list of fixed argv strings.');
            }
            foreach ($arguments as $argument) {
                if (!is_string($argument) || str_contains($argument, "\0")) {
                    throw $fail('arguments must contain only NUL-free strings.');
                }
            }
            $schema = $definition['inputSchema'] ?? ['type' => 'object', 'properties' => []];
            if (!is_array($schema) || ($schema['type'] ?? null) !== 'object' || !is_array($schema['properties'] ?? null)) {
                throw $fail('inputSchema must have type object and a properties object.');
            }
            if (array_diff(array_keys($schema), ['type', 'properties', 'required', 'additionalProperties', 'title', 'description']) !== []) {
                throw $fail('inputSchema supports only type, properties, required, additionalProperties, title and description; references and custom dialects are not supported.');
            }
            if ($schema['properties'] !== []) {
                foreach ($arguments as $argument) {
                    if (str_starts_with($argument, '-') && preg_match('/^--[a-zA-Z][a-zA-Z0-9-]*(?:=.*)?$/sD', $argument) !== 1) {
                        throw $fail('Fixed options must use canonical long names when dynamic inputs are present; use --key=value instead of short aliases.');
                    }
                }
            }
            foreach ($schema['properties'] as $property => $propertySchema) {
                if (!is_string($property) || preg_match('/^[a-zA-Z][a-zA-Z0-9-]*$/D', $property) !== 1 || $property === 'allowWrite' || !is_array($propertySchema)) {
                    throw $fail('Input names must be CLI long-option names; allowWrite is reserved.');
                }
                if (!in_array($propertySchema['type'] ?? null, ['string', 'integer', 'number', 'boolean'], true)) {
                    throw $fail('Inputs must declare a scalar type (string, integer, number or boolean). Pass JSON payloads as strings.');
                }
                if (array_diff(array_keys($propertySchema), ['type', 'title', 'description', 'default', 'enum', 'const', 'minimum', 'maximum', 'exclusiveMinimum', 'exclusiveMaximum', 'multipleOf', 'minLength', 'maxLength', 'pattern']) !== []) {
                    throw $fail('Input schemas support only scalar constraints and metadata; references, composition and extension keywords are not supported.');
                }
                foreach ($arguments as $argument) {
                    if ($argument === '--' . $property || str_starts_with($argument, '--' . $property . '=')) {
                        throw $fail('An input cannot override a fixed argument: ' . $property);
                    }
                }
            }
            $required = $schema['required'] ?? [];
            if (!is_array($required) || !array_is_list($required)) {
                throw $fail('inputSchema.required must be a list of declared input names.');
            }
            foreach ($required as $property) {
                if (!is_string($property) || !array_key_exists($property, $schema['properties'])) {
                    throw $fail('inputSchema.required must name declared inputs.');
                }
            }
            $schema['additionalProperties'] = false;
            if (!$readOnly) {
                $schema['properties']['allowWrite'] = [
                    'type' => 'boolean',
                    'const' => true,
                    'description' => 'Set true only after the user approves this write-capable command.',
                ];
                $required[] = 'allowWrite';
            }
            $schema['required'] = array_values(array_unique($required));

            $tools[] = new self(new Tool(
                name: $name,
                title: $title,
                inputSchema: $schema,
                description: $description,
                annotations: new ToolAnnotations(readOnlyHint: $readOnly, destructiveHint: !$readOnly, openWorldHint: true),
            ), $command, $arguments, $readOnly, $timeout);
        }

        return $tools;
    }

    public function register(Registry $registry): void
    {
        if ($registry->hasTool($this->tool->name)) {
            throw new \InvalidArgumentException('Duplicate custom tool: ' . $this->tool->name);
        }
        $registry->registerTool($this->tool, function (RequestContext $context): mixed {
            $request = $context->getRequest();
            if (!$request instanceof CallToolRequest) {
                throw new \LogicException('Expected a tool call.');
            }
            if (!$this->readOnly && ($request->arguments['allowWrite'] ?? false) !== true) {
                throw new ToolCallException('Write-capable custom tools require allowWrite=true.');
            }
            foreach ($this->tool->inputSchema['required'] ?? [] as $name) {
                if (!array_key_exists($name, $request->arguments)) {
                    throw new ToolCallException('Required CLI input: ' . $name);
                }
            }
            $arguments = $this->arguments;
            $properties = $this->tool->inputSchema['properties'];
            foreach ($request->arguments as $name => $value) {
                if (!is_array($properties) || !array_key_exists($name, $properties)) {
                    throw new \InvalidArgumentException('Undeclared CLI input: ' . $name);
                }
                if ($name === 'allowWrite') {
                    continue;
                }
                $valid = match ($properties[$name]['type']) {
                    'string' => is_string($value),
                    'integer' => is_int($value) || (is_float($value) && is_finite($value) && floor($value) === $value),
                    'number' => is_int($value) || is_float($value),
                    'boolean' => is_bool($value),
                    default => false,
                };
                if (!$valid) {
                    throw new ToolCallException('Invalid type for CLI input: ' . $name);
                }
                // One argv element per option: values cannot inject another flag.
                if (is_bool($value)) {
                    if ($value) {
                        $arguments[] = '--' . $name;
                    }
                } else {
                    $value = is_string($value) ? $value : json_encode($value, JSON_THROW_ON_ERROR);
                    if (str_contains($value, "\0")) {
                        throw new \InvalidArgumentException('CLI input must not contain NUL bytes.');
                    }
                    $arguments[] = '--' . $name . '=' . $value;
                }
            }

            $result = (new CliTools())->runCliCommand(
                command: $this->command,
                arguments: $arguments,
                allowWrite: !$this->readOnly && ($request->arguments['allowWrite'] ?? false) === true,
                timeoutSeconds: $this->timeoutSeconds,
                context: $context,
            );
            if ($result instanceof CallToolResult) {
                return new CallToolResult(
                    content: $result->content,
                    isError: ($result->structuredContent['ok'] ?? false) !== true,
                    structuredContent: $result->structuredContent,
                );
            }
            return $result;
        });
    }
}
