<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Support;

final class FieldSchemaHelper
{
    /**
     * @param array<mixed> $data
     * @return array<string, array{type: string|null, _schemaRef?: array{panel: string, updateSchema: string}, _nestedBlockFields?: array<string, array<string, array{type: string|null, _schemaRef?: array{panel: string, updateSchema: string}}>>}>
     */
    public static function fromBlueprintData(array $data, bool $includeNestedBlocks = false): array
    {
        $definitions = self::extractFieldDefinitions($data);

        return self::buildSchemaMap($definitions, $includeNestedBlocks);
    }

    /**
     * @param array<mixed> $fields
     * @return array<string, array{type: string|null, _schemaRef?: array{panel: string, updateSchema: string}, _nestedBlockFields?: array<string, array<string, array{type: string|null, _schemaRef?: array{panel: string, updateSchema: string}}>>}>
     */
    public static function fromFieldDefinitions(array $fields, bool $includeNestedBlocks = false): array
    {
        $definitions = [];

        foreach ($fields as $fieldKey => $fieldDefinition) {
            if (!is_string($fieldKey) || trim($fieldKey) === '') {
                continue;
            }

            if (is_array($fieldDefinition)) {
                $definitions[$fieldKey] = $fieldDefinition;
            } else {
                $definitions[$fieldKey] = [];
            }
        }

        return self::buildSchemaMap($definitions, $includeNestedBlocks);
    }

    /**
     * @param array<mixed> $data
     * @return array<string, array>
     */
    private static function extractFieldDefinitions(array $data): array
    {
        $definitions = [];

        $walk = function (mixed $node, bool $insideFieldDefinition) use (&$walk, &$definitions): void {
            if (!is_array($node)) {
                return;
            }

            foreach ($node as $key => $value) {
                if ($key === 'fields' && is_array($value) && $insideFieldDefinition === false) {
                    foreach ($value as $fieldKey => $fieldDefinition) {
                        if (!is_string($fieldKey) || trim($fieldKey) === '') {
                            continue;
                        }

                        $definitions[$fieldKey] = is_array($fieldDefinition) ? $fieldDefinition : [];

                        $walk($fieldDefinition, true);
                    }

                    continue;
                }

                if (is_array($value)) {
                    $walk($value, $insideFieldDefinition);
                }
            }
        };

        $walk($data, false);

        return $definitions;
    }

    /**
     * @param array<string, array> $definitions
     * @return array<string, array{type: string|null, _schemaRef?: array{panel: string, updateSchema: string}, _nestedBlockFields?: array<string, array<string, array{type: string|null, _schemaRef?: array{panel: string, updateSchema: string}}>>}>
     */
    private static function buildSchemaMap(array $definitions, bool $includeNestedBlocks): array
    {
        $schemas = [];

        foreach ($definitions as $fieldKey => $definition) {
            $type = null;
            if (is_array($definition)) {
                $type = $definition['type'] ?? null;
            }

            $type = self::normalizeType($type);
            $entry = ['type' => $type];

            if (is_string($type) && self::isSlug($type)) {
                $entry['_schemaRef'] = [
                    'panel' => 'kirby://field/' . $type,
                    'updateSchema' => 'kirby://field/' . $type . '/update-schema',
                ];
            }

            if ($includeNestedBlocks === true && ($type === 'blocks' || $type === 'layout')) {
                $nested = self::resolveNestedBlockFields(is_array($definition) ? $definition : []);
                if ($nested !== []) {
                    $entry['_nestedBlockFields'] = $nested;
                }
            }

            $schemas[$fieldKey] = $entry;
        }

        ksort($schemas);

        return $schemas;
    }

    /**
     * @param array<string, mixed> $definition
     * @return array<string, array<string, array{type: string|null, _schemaRef?: array{panel: string, updateSchema: string}}>>
     */
    private static function resolveNestedBlockFields(array $definition): array
    {
        if (self::canResolveBlockFieldsets() === false) {
            return [];
        }

        $fieldsets = $definition['fieldsets'] ?? null;
        if (!is_array($fieldsets) || $fieldsets === []) {
            $fieldsets = null;
        }

        try {
            $collection = \Kirby\Cms\Fieldsets::factory($fieldsets);
        } catch (\Throwable) {
            return [];
        }

        $nested = [];

        foreach ($collection as $fieldset) {
            if (!is_object($fieldset) || !method_exists($fieldset, 'type') || !method_exists($fieldset, 'fields')) {
                continue;
            }

            $type = $fieldset->type();
            if (!is_string($type) || $type === '') {
                continue;
            }

            $fields = $fieldset->fields();
            if (!is_array($fields) || $fields === []) {
                $nested[$type] = [];
                continue;
            }

            $nested[$type] = self::fromFieldDefinitions($fields, false);
        }

        ksort($nested);

        return $nested;
    }

    private static function canResolveBlockFieldsets(): bool
    {
        if (!class_exists(\Kirby\Cms\Fieldsets::class)) {
            return false;
        }

        if (!class_exists(\Kirby\Cms\App::class)) {
            return false;
        }

        return \Kirby\Cms\App::instance(null, true) instanceof \Kirby\Cms\App;
    }

    private static function normalizeType(mixed $type): ?string
    {
        if (!is_string($type)) {
            return null;
        }

        $type = strtolower(trim($type));

        return $type !== '' ? $type : null;
    }

    private static function isSlug(string $value): bool
    {
        return preg_match('/^[a-z0-9][a-z0-9-]*$/u', $value) === 1;
    }
}
