<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\Http\UploadTickets;
use Kirby\Filesystem\Dir;

it('binds tickets to their user and immutable operation and consumes them once', function (): void {
    $root = sys_get_temp_dir() . '/kirby-ticket-test-' . bin2hex(random_bytes(8));
    try {
        $store = new UploadTickets($root);
        $input = ['filename' => 'report.csv', 'content' => ['caption' => 'Original']];
        $issued = $store->issue('alice', $input, ['maxBytes' => 123]);
        expect($issued['expiresAt'])->toBeGreaterThan(time())->toBeLessThanOrEqual(time() + 600);
        expect(fn () => $store->claim($issued['ticket'], 'bob'))->toThrow(RuntimeException::class);
        $claimed = $store->claim($issued['ticket'], 'alice');
        expect($claimed['input'])->toBe($input);
        expect($claimed['preview']['maxBytes'])->toBe(123);
        expect(fn () => $store->claim($issued['ticket'], 'alice'))->toThrow(RuntimeException::class);
        expect(fn () => $store->claim('../invalid', 'alice'))->toThrow(InvalidArgumentException::class);
    } finally {
        Dir::remove($root);
    }
});

it('rejects persisted expired tickets without allowing a fresh claim', function (): void {
    $root = sys_get_temp_dir() . '/kirby-ticket-test-' . bin2hex(random_bytes(8));
    try {
        $store = new UploadTickets($root);
        $issued = $store->issue('alice', ['filename' => 'report.csv'], ['maxBytes' => 123]);
        // Simulate a ticket left on disk by an earlier HTTP worker.
        $path = glob($root . '/.kirby-mcp/uploads/*.json')[0];
        $json = file_get_contents($path);
        if ($json === false) {
            throw new RuntimeException('Missing issued ticket record.');
        }
        $record = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        $record['expiresAt'] = time() - 1;
        file_put_contents($path, json_encode($record, JSON_THROW_ON_ERROR));
        expect(fn () => $store->claim($issued['ticket'], 'alice'))->toThrow(RuntimeException::class, 'Expired');
    } finally {
        Dir::remove($root);
    }
});
