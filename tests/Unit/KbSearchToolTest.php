<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\Tools\KbTools;

it('searches the local Kirby knowledge base with fuzzy matching', function (): void {
    $tools = new KbTools();

    $data = $tools->search('config options', limit: 50);

    expect($data)->toHaveKey('normalizedQuery', 'config options');
    expect($data)->toHaveKey('needles', ['config', 'options']);
    expect($data)->toHaveKey('results');
    expect($data['results'])->toBeArray();

    $config = null;
    foreach ($data['results'] as $result) {
        if (($result['file'] ?? null) === 'kb/kirby/scenarios/33-use-placeholders-str-template.md') {
            $config = $result;
            break;
        }
    }

    expect($config)->toBeArray();
    expect($config['score'] ?? null)->toBe(2);
    expect($config['matchedNeedles'] ?? null)->toContain('config');
    expect($config['matchedNeedles'] ?? null)->toContain('options');

    expect($data)->toHaveKey('documents');
    expect($data['documents'])->toBeArray();
    expect($data)->toHaveKey('document');
    expect($data['document'])->toBeArray();
    expect($data['document'])->toHaveKey('markdown');
    expect($data['document']['markdown'])->toBeString()->not()->toBe('');

    $typo = $tools->search('confg, optons', limit: 50);
    $files = array_map(static fn (array $row): string => (string) ($row['file'] ?? ''), $typo['results'] ?? []);
    expect($files)->toContain('kb/kirby/scenarios/33-use-placeholders-str-template.md');
});

it('excludes PLAN.md files from kb search', function (): void {
    $tools = new KbTools();

    $data = $tools->search('playbooks', limit: 50, fetch: 0);

    $files = array_map(static fn (array $row): string => (string) ($row['file'] ?? ''), $data['results'] ?? []);
    expect($files)->not()->toContain('kb/kirby/scenarios/PLAN.md');
    expect($files)->not()->toContain('kb/kirby/glossary/PLAN.md');
    expect($data['matchCount'] ?? null)->toBe(0);
});
