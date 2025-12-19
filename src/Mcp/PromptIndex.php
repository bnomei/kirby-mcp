<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp;

use Mcp\Capability\Attribute\CompletionProvider;
use Mcp\Capability\Attribute\McpPrompt;
use Mcp\Schema\Icon;

/**
 * Canonical prompt index (name + args + meta).
 *
 * This is built via reflection from prompt methods annotated with #[McpPrompt].
 */
final class PromptIndex
{
    /**
     * @var array<string, array{
     *   name: string,
     *   description: string,
     *   args: array<int, array{
     *     name: string,
     *     type: string,
     *     required: bool,
     *     default: mixed,
     *     completion: null|array{values?: array<int, int|float|string>, enum?: string, providerClass?: string}
     *   }>,
     *   meta: null|array<string, mixed>,
     *   icons: null|array<int, array{src: string, mimeType?: string, sizes?: array<int, string>}>,
     *   generator: array{class: class-string, method: string},
     *   resource: string
     * }>|null
     */
    private static ?array $cache = null;

    public static function clearCache(): void
    {
        self::$cache = null;
    }

    /**
     * @return array<int, array{
     *   name: string,
     *   description: string,
     *   args: array<int, array{
     *     name: string,
     *     type: string,
     *     required: bool,
     *     default: mixed,
     *     completion: null|array{values?: array<int, int|float|string>, enum?: string, providerClass?: string}
     *   }>,
     *   meta: null|array<string, mixed>,
     *   icons: null|array<int, array{src: string, mimeType?: string, sizes?: array<int, string>}>,
     *   generator: array{class: class-string, method: string},
     *   resource: string
     * }>
     */
    public static function all(): array
    {
        if (self::$cache !== null) {
            return array_values(self::$cache);
        }

        $prompts = [];

        $promptsDir = __DIR__ . DIRECTORY_SEPARATOR . 'Prompts';
        $srcRoot = dirname(__DIR__);

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($promptsDir));
        foreach ($iterator as $file) {
            if ($file->isFile() === false || strtolower($file->getExtension()) !== 'php') {
                continue;
            }

            $path = $file->getPathname();
            $relative = ltrim(substr($path, strlen($srcRoot)), DIRECTORY_SEPARATOR);
            $relative = preg_replace('/\\.php$/i', '', $relative) ?? $relative;
            $class = 'Bnomei\\KirbyMcp\\' . str_replace(DIRECTORY_SEPARATOR, '\\', $relative);

            if (!class_exists($class)) {
                continue;
            }

            $reflection = new \ReflectionClass($class);
            foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                $promptAttributes = $method->getAttributes(McpPrompt::class);
                if ($promptAttributes === []) {
                    continue;
                }

                /** @var McpPrompt $mcpPrompt */
                $mcpPrompt = $promptAttributes[0]->newInstance();
                $name = is_string($mcpPrompt->name) ? trim($mcpPrompt->name) : '';
                if ($name === '') {
                    $name = $method->getName();
                }

                $name = trim($name);
                if ($name === '') {
                    continue;
                }

                $description = is_string($mcpPrompt->description) ? trim($mcpPrompt->description) : '';
                if ($description === '') {
                    $docComment = $method->getDocComment();
                    $description = self::docblockSummary($docComment !== false ? $docComment : null);
                }

                $icons = self::normalizeIcons($mcpPrompt->icons);

                $args = [];
                foreach ($method->getParameters() as $parameter) {
                    $args[] = self::normalizePromptArg($parameter);
                }

                $prompts[$name] = [
                    'name' => $name,
                    'description' => $description,
                    'args' => $args,
                    'meta' => is_array($mcpPrompt->meta) ? $mcpPrompt->meta : null,
                    'icons' => $icons,
                    'generator' => [
                        'class' => $class,
                        'method' => $method->getName(),
                    ],
                    'resource' => 'kirby://prompt/' . rawurlencode($name),
                ];
            }
        }

        ksort($prompts);
        self::$cache = $prompts;

        return array_values(self::$cache);
    }

    /**
     * @return array{
     *   name: string,
     *   description: string,
     *   args: array<int, array{
     *     name: string,
     *     type: string,
     *     required: bool,
     *     default: mixed,
     *     completion: null|array{values?: array<int, int|float|string>, enum?: string, providerClass?: string}
     *   }>,
     *   meta: null|array<string, mixed>,
     *   icons: null|array<int, array{src: string, mimeType?: string, sizes?: array<int, string>}>,
     *   generator: array{class: class-string, method: string},
     *   resource: string
     * }|null
     */
    public static function get(string $name): ?array
    {
        $name = trim($name);
        if ($name === '') {
            return null;
        }

        if (self::$cache === null) {
            self::all();
        }

        return self::$cache[$name] ?? null;
    }

    /**
     * Render prompt messages by calling its generator method.
     *
     * @param array<string, mixed> $args
     * @return array<int, array{role: string, content: string}>
     */
    public static function renderMessages(string $name, array $args = []): array
    {
        $prompt = self::get($name);
        if ($prompt === null) {
            throw new \InvalidArgumentException('Unknown prompt: ' . $name);
        }

        $class = $prompt['generator']['class'];
        $methodName = $prompt['generator']['method'];

        $instance = new $class();
        $method = new \ReflectionMethod($instance, $methodName);

        $callArgs = [];
        foreach ($method->getParameters() as $parameter) {
            $paramName = $parameter->getName();

            if (array_key_exists($paramName, $args)) {
                $callArgs[] = $args[$paramName];
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $callArgs[] = $parameter->getDefaultValue();
                continue;
            }

            if ($parameter->allowsNull()) {
                $callArgs[] = null;
                continue;
            }

            throw new \InvalidArgumentException('Missing required prompt argument: ' . $paramName);
        }

        $messages = $method->invokeArgs($instance, $callArgs);
        if (!is_array($messages)) {
            throw new \RuntimeException('Prompt generator did not return an array.');
        }

        return $messages;
    }

    /**
     * @return array{name: string, type: string, required: bool, default: mixed, completion: null|array{values?: array<int, int|float|string>, enum?: string, providerClass?: string}}
     */
    private static function normalizePromptArg(\ReflectionParameter $parameter): array
    {
        $type = $parameter->getType();
        $default = null;
        $required = true;

        if ($parameter->isDefaultValueAvailable()) {
            $default = $parameter->getDefaultValue();
            $required = false;
        } elseif ($parameter->isVariadic()) {
            $required = false;
        }

        $completion = null;
        $completionAttributes = $parameter->getAttributes(CompletionProvider::class);
        if ($completionAttributes !== []) {
            /** @var CompletionProvider $provider */
            $provider = $completionAttributes[0]->newInstance();

            $completion = [];
            if (is_array($provider->values)) {
                $completion['values'] = $provider->values;
            }
            if (is_string($provider->enum) && $provider->enum !== '') {
                $completion['enum'] = $provider->enum;
            }
            if (is_string($provider->providerClass) && $provider->providerClass !== '') {
                $completion['providerClass'] = $provider->providerClass;
            }
            if ($completion === []) {
                $completion = null;
            }
        }

        return [
            'name' => $parameter->getName(),
            'type' => self::typeToString($type),
            'required' => $required,
            'default' => $default,
            'completion' => $completion,
        ];
    }

    private static function typeToString(?\ReflectionType $type): string
    {
        if ($type === null) {
            return 'mixed';
        }

        if ($type instanceof \ReflectionNamedType) {
            $name = $type->getName();

            return $type->allowsNull() && $name !== 'mixed' ? $name . '|null' : $name;
        }

        if ($type instanceof \ReflectionUnionType) {
            $parts = [];
            foreach ($type->getTypes() as $named) {
                $parts[] = $named->getName();
            }

            return implode('|', array_values(array_unique($parts)));
        }

        return 'mixed';
    }

    /**
     * @param ?array<int, Icon> $icons
     * @return null|array<int, array{src: string, mimeType?: string, sizes?: array<int, string>}>
     */
    private static function normalizeIcons(?array $icons): ?array
    {
        if (!is_array($icons) || $icons === []) {
            return null;
        }

        $normalized = [];
        foreach ($icons as $icon) {
            if (!$icon instanceof Icon) {
                continue;
            }

            $normalized[] = $icon->jsonSerialize();
        }

        return $normalized !== [] ? $normalized : null;
    }

    private static function docblockSummary(?string $docblock): string
    {
        if (!is_string($docblock) || trim($docblock) === '') {
            return '';
        }

        $docblock = trim($docblock);
        $docblock = preg_replace('/^\\/\\*\\*\\s*/', '', $docblock) ?? $docblock;
        $docblock = preg_replace('/\\s*\\*\\/$/', '', $docblock) ?? $docblock;

        $lines = preg_split('/\\R/', $docblock) ?: [];
        foreach ($lines as $line) {
            $line = preg_replace('/^\\s*\\*\\s?/', '', $line) ?? $line;
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '@')) {
                continue;
            }

            return $line;
        }

        return '';
    }
}
