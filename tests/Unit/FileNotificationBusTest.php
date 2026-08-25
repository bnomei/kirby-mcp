<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\Subscription\FileNotificationBus;
use Mcp\Schema\Notification\ResourceUpdatedNotification;

function kirbyMcpFileBusRoot(): string
{
    $root = sys_get_temp_dir() . '/kirby-mcp-bus-' . bin2hex(random_bytes(6));
    mkdir($root, 0700);

    return $root;
}

function removeKirbyMcpFileBusRoot(string $root): void
{
    if (!is_dir($root) || is_link($root)) {
        @unlink($root);

        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );
    foreach ($iterator as $item) {
        $item->isDir() && !$item->isLink() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }
    rmdir($root);
}

it('shares activated notifications across file bus instances and starts muted', function (): void {
    $root = kirbyMcpFileBusRoot();

    try {
        $publisher = new FileNotificationBus($root);
        $reader = new FileNotificationBus($root);
        $cursor = $reader->cursor();

        $publisher->publish(new ResourceUpdatedNotification('kirby://page/ignored'));
        expect($reader->since($cursor)[0])->toBe([]);

        $publisher->activate();
        $publisher->publish(new ResourceUpdatedNotification('kirby://page/home'));
        [$notifications, $next] = $reader->since($cursor);

        expect($notifications)->toHaveCount(1)
            ->and($notifications[0])->toBeInstanceOf(ResourceUpdatedNotification::class)
            ->and($notifications[0]->jsonSerialize()['params']['uri'] ?? null)->toBe('kirby://page/home')
            ->and($next)->toBeGreaterThan($cursor)
            ->and(fileperms($root . '/.kirby-mcp/http-notifications') & 0777)->toBe(0700)
            ->and(fileperms($root . '/.kirby-mcp/http-notifications/state.json') & 0777)->toBe(0600)
            ->and(fileperms($root . '/.kirby-mcp/http-notifications/state.lock') & 0777)->toBe(0600);
    } finally {
        removeKirbyMcpFileBusRoot($root);
    }
});

it('bounds its backlog and drops oversized individual notifications', function (): void {
    $root = kirbyMcpFileBusRoot();

    try {
        $bus = new FileNotificationBus($root);
        $cursor = $bus->cursor();
        $bus->activate();

        for ($index = 0; $index < 300; $index++) {
            $bus->publish(new ResourceUpdatedNotification('kirby://page/' . $index));
        }

        [$notifications, $head] = $bus->since($cursor);
        expect($notifications)->toHaveCount(256)
            ->and($notifications[0]->jsonSerialize()['params']['uri'] ?? null)->toBe('kirby://page/44')
            ->and($head)->toBe($cursor + 300);

        $bus->publish(new ResourceUpdatedNotification('kirby://page/' . str_repeat('x', 1100000)));
        $statePath = $root . '/.kirby-mcp/http-notifications/state.json';
        [$afterOversized, $headAfterOversized] = $bus->since($head);
        expect(filesize($statePath))->toBeLessThanOrEqual(1048576)
            ->and($afterOversized)->toBe([])
            ->and($headAfterOversized)->toBe($head)
            ->and($bus->since($cursor)[0])->toHaveCount(256);
    } finally {
        removeKirbyMcpFileBusRoot($root);
    }
});

it('recovers corrupt state, expires stale entries, and advances past malformed ones', function (): void {
    $root = kirbyMcpFileBusRoot();

    try {
        $bus = new FileNotificationBus($root);
        $statePath = $root . '/.kirby-mcp/http-notifications/state.json';
        expect($bus->cursor())->toBe(0);

        file_put_contents($statePath, '{broken');
        chmod($statePath, 0600);

        $resetCursor = $bus->cursor();
        $reset = json_decode((string) file_get_contents($statePath), true, flags: JSON_THROW_ON_ERROR);
        expect($reset['version'] ?? null)->toBe(1)
            ->and($reset['entries'] ?? null)->toBe([])
            ->and($resetCursor)->toBe(0);

        $state = [
            'version' => 1,
            'next' => $resetCursor + 2,
            'entries' => [
                [
                    'sequence' => $resetCursor,
                    'publishedAt' => microtime(true) - 121,
                    'notification' => (new ResourceUpdatedNotification('kirby://page/stale'))->jsonSerialize(),
                ],
                [
                    'sequence' => $resetCursor + 1,
                    'publishedAt' => microtime(true),
                    'notification' => ['not' => 'a JSON-RPC notification'],
                ],
            ],
        ];
        file_put_contents($statePath, json_encode($state, JSON_THROW_ON_ERROR));
        chmod($statePath, 0600);

        [$notifications, $head] = $bus->since($resetCursor);
        expect($notifications)->toBe([])
            ->and($head)->toBe($resetCursor + 2);
    } finally {
        removeKirbyMcpFileBusRoot($root);
    }
});

it('rejects symlinked notification storage', function (): void {
    $root = kirbyMcpFileBusRoot();
    $target = kirbyMcpFileBusRoot();

    try {
        symlink($target, $root . '/.kirby-mcp');

        expect(fn (): FileNotificationBus => new FileNotificationBus($root))
            ->toThrow(RuntimeException::class, 'Refusing symlinked Kirby MCP storage.');
    } finally {
        removeKirbyMcpFileBusRoot($root);
        removeKirbyMcpFileBusRoot($target);
    }
});
