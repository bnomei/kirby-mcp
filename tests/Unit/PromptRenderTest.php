<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\PromptIndex;

it('renders prompt messages with default or completion args', function (): void {
    foreach (PromptIndex::all() as $prompt) {
        $args = [];

        foreach ($prompt['args'] as $arg) {
            if (($arg['required'] ?? false) !== true) {
                continue;
            }

            $completion = $arg['completion'] ?? null;
            if (is_array($completion) && isset($completion['values'][0])) {
                $args[$arg['name']] = $completion['values'][0];
                continue;
            }

            $type = strtolower((string) ($arg['type'] ?? 'mixed'));
            $args[$arg['name']] = match (true) {
                str_contains($type, 'int') => 0,
                str_contains($type, 'float') => 0.0,
                str_contains($type, 'bool') => false,
                str_contains($type, 'array') => [],
                default => 'default',
            };
        }

        $messages = PromptIndex::renderMessages($prompt['name'], $args);

        expect($messages)->toBeArray()->not()->toBeEmpty();

        foreach ($messages as $message) {
            expect($message)->toBeArray();
            expect($message['role'] ?? null)->toBeString()->not()->toBe('');
            expect($message['content'] ?? null)->toBeString()->not()->toBe('');
        }
    }
});
