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
}
