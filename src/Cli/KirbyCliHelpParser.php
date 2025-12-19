<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Cli;

final class KirbyCliHelpParser
{
    /**
     * Parse `kirby help` (or running `kirby` without args) output.
     *
     * @return array{
     *   cliVersion: string|null,
     *   sections: array<string, array<int, string>>,
     *   commands: array<int, string>
     * }
     */
    public static function parse(string $stdout): array
    {
        $cliVersion = null;
        $sections = [];
        $allCommands = [];

        $currentSection = null;

        foreach (preg_split('/\\R/u', $stdout) ?: [] as $line) {
            $line = trim((string)$line);
            if ($line === '') {
                continue;
            }

            if ($cliVersion === null && preg_match('/^Kirby\\s+CLI\\s+([0-9]+\\.[0-9]+\\.[0-9]+)$/i', $line, $m) === 1) {
                $cliVersion = $m[1];
                continue;
            }

            if (preg_match('/^([A-Za-z][A-Za-z0-9 _-]*)\\s+commands:$/', $line, $m) === 1) {
                $currentSection = strtolower(trim(str_replace(' ', '_', $m[1])));
                if (!isset($sections[$currentSection])) {
                    $sections[$currentSection] = [];
                }
                continue;
            }

            if (preg_match('/^-\\s*kirby\\s+(.+)$/i', $line, $m) === 1) {
                $command = trim($m[1]);
                if ($command === '') {
                    continue;
                }

                $allCommands[] = $command;

                if ($currentSection === null) {
                    $currentSection = 'unknown';
                    $sections[$currentSection] ??= [];
                }

                $sections[$currentSection][] = $command;
            }
        }

        foreach ($sections as $name => $commands) {
            $commands = array_values(array_unique($commands));
            sort($commands);
            $sections[$name] = $commands;
        }

        $allCommands = array_values(array_unique($allCommands));
        sort($allCommands);

        ksort($sections);

        return [
            'cliVersion' => $cliVersion,
            'sections' => $sections,
            'commands' => $allCommands,
        ];
    }

    /**
     * Parse `kirby <command> --help` output (CLImate usage output).
     *
     * @return array{
     *   description: string|null,
     *   usage: string|null,
     *   required: array<int, array{
     *     name: string,
     *     kind: 'argument'|'option',
     *     aliases: array<int, string>,
     *     description: string|null
     *   }>,
     *   optional: array<int, array{
     *     name: string,
     *     kind: 'argument'|'option',
     *     aliases: array<int, string>,
     *     description: string|null
     *   }>
     * }
     */
    public static function parseCommandUsage(string $stdout): array
    {
        $lines = preg_split('/\\R/u', $stdout) ?: [];

        $descriptionLines = [];
        $usage = null;

        foreach ($lines as $line) {
            $trim = trim((string)$line);
            if ($trim === '') {
                continue;
            }

            if (preg_match('/^Usage:\\s*(.+)$/', $trim, $m) === 1) {
                $usage = trim($m[1]);
                break;
            }

            $descriptionLines[] = $trim;
        }

        $description = trim(implode("\n", $descriptionLines));
        if ($description === '') {
            $description = null;
        }

        $required = self::parseArgumentsSection($lines, 'Required Arguments:');
        $optional = self::parseArgumentsSection($lines, 'Optional Arguments:');

        return [
            'description' => $description,
            'usage' => $usage,
            'required' => $required,
            'optional' => $optional,
        ];
    }

    /**
     * @param array<int, string> $lines
     * @return array<int, array{
     *   name: string,
     *   kind: 'argument'|'option',
     *   aliases: array<int, string>,
     *   description: string|null
     * }>
     */
    private static function parseArgumentsSection(array $lines, string $header): array
    {
        $startIndex = null;
        foreach ($lines as $i => $line) {
            if (trim((string)$line) === $header) {
                $startIndex = $i + 1;
                break;
            }
        }

        if ($startIndex === null) {
            return [];
        }

        $endIndex = count($lines);
        for ($i = $startIndex; $i < count($lines); $i++) {
            $trim = trim((string)$lines[$i]);
            if ($trim === 'Required Arguments:' || $trim === 'Optional Arguments:' || str_starts_with($trim, 'Usage:')) {
                $endIndex = $i;
                break;
            }
        }

        $nameIndent = null;
        for ($i = $startIndex; $i < $endIndex; $i++) {
            $line = rtrim((string)$lines[$i], "\r\n");
            if (trim($line) === '') {
                continue;
            }

            $indent = strlen($line) - strlen(ltrim($line));
            if ($indent > 0 && ($nameIndent === null || $indent < $nameIndent)) {
                $nameIndent = $indent;
            }
        }

        if ($nameIndent === null) {
            return [];
        }

        $items = [];
        $current = null;

        for ($i = $startIndex; $i < $endIndex; $i++) {
            $line = rtrim((string)$lines[$i], "\r\n");
            if (trim($line) === '') {
                continue;
            }

            $indent = strlen($line) - strlen(ltrim($line));
            $trim = trim($line);

            if ($indent === $nameIndent) {
                if (is_array($current)) {
                    $items[] = self::finalizeArgumentItem($current);
                }

                $current = [
                    'raw' => $trim,
                    'desc' => [],
                ];

                continue;
            }

            if (is_array($current) && $indent > $nameIndent) {
                $current['desc'][] = $trim;
            }
        }

        if (is_array($current)) {
            $items[] = self::finalizeArgumentItem($current);
        }

        return array_values(array_filter($items, static function (array $item): bool {
            return is_string($item['name'] ?? null) && $item['name'] !== '';
        }));
    }

    /**
     * @param array{raw:string, desc: array<int, string>} $current
     * @return array{
     *   name: string,
     *   kind: 'argument'|'option',
     *   aliases: array<int, string>,
     *   description: string|null
     * }
     */
    private static function finalizeArgumentItem(array $current): array
    {
        $raw = trim($current['raw']);
        $aliases = array_values(array_filter(array_map('trim', explode(',', $raw)), static fn (string $part): bool => $part !== ''));

        $kind = 'argument';
        if (isset($aliases[0]) && str_starts_with($aliases[0], '-')) {
            $kind = 'option';
        }

        $name = $raw;
        if ($kind === 'option') {
            foreach ($aliases as $alias) {
                if (str_starts_with($alias, '--')) {
                    $name = $alias;
                    break;
                }
            }
        } else {
            $name = $aliases[0] ?? $raw;
        }

        $description = trim(implode(' ', $current['desc']));
        if ($description === '') {
            $description = null;
        }

        return [
            'name' => $name,
            'kind' => $kind,
            'aliases' => $aliases,
            'description' => $description,
        ];
    }
}
