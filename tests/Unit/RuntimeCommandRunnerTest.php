<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Cli\KirbyCliRunner;
use Bnomei\KirbyMcp\Mcp\Support\RuntimeCommandRunner;
use Bnomei\KirbyMcp\Mcp\Support\RuntimeContextInterface;

final class FakeRuntimeContext implements RuntimeContextInterface
{
    public function __construct(
        private string $projectRoot,
        private string $commandsRoot,
        private array $env = [],
        private ?string $host = null,
    ) {
    }

    public function projectRoot(): string
    {
        return $this->projectRoot;
    }

    public function host(): ?string
    {
        return $this->host;
    }

    public function env(): array
    {
        return $this->env;
    }

    public function commandsRoot(): string
    {
        return $this->commandsRoot;
    }

    public function commandFile(string $relativePath): string
    {
        $relativePath = ltrim($relativePath, '/');
        $relativePath = str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

        return rtrim($this->commandsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $relativePath;
    }
}

function runtimeTestTempDir(string $suffix): string
{
    $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kirby-mcp-runtime-' . $suffix . '-' . bin2hex(random_bytes(4));
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    return $dir;
}

function writeRuntimeTestScript(string $path, string $body): void
{
    $contents = "#!/usr/bin/env php\n<?php\n" . $body . "\n";
    file_put_contents($path, $contents);
    chmod($path, 0755);
}

function writeRuntimeCommandFile(string $commandsRoot, string $relativePath): void
{
    $path = rtrim($commandsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    file_put_contents($path, "<?php\n");
}

function removeRuntimeTestDir(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $item) {
        if ($item->isDir()) {
            rmdir($item->getPathname());
        } else {
            unlink($item->getPathname());
        }
    }

    rmdir($dir);
}

function withKirbyCliBinary(string $path, callable $callback): void
{
    $key = KirbyCliRunner::ENV_KIRBY_BIN;
    $previous = getenv($key);
    putenv($key . '=' . $path);

    try {
        $callback();
    } finally {
        if ($previous === false) {
            putenv($key);
        } else {
            putenv($key . '=' . $previous);
        }
    }
}

it('skips parsing when CLI exits non-zero and includes stderr', function (): void {
    $root = runtimeTestTempDir('fail');
    $commandsRoot = $root . DIRECTORY_SEPARATOR . 'commands';
    $relativeCommand = 'mcp/test.php';
    writeRuntimeCommandFile($commandsRoot, $relativeCommand);

    $binary = $root . DIRECTORY_SEPARATOR . 'kirby-cli';
    writeRuntimeTestScript($binary, <<<'PHP'
fwrite(STDERR, "boom");
echo "__KIRBY_MCP_JSON__" . json_encode(['ok' => true]) . "__KIRBY_MCP_JSON_END__";
exit(2);
PHP);

    try {
        withKirbyCliBinary($binary, function () use ($root, $commandsRoot, $relativeCommand): void {
            $runtime = new FakeRuntimeContext($root, $commandsRoot);
            $runner = new RuntimeCommandRunner($runtime, new KirbyCliRunner());

            $result = $runner->runMarkedJson($relativeCommand, ['test'], 5);

            expect($result->installed)->toBeTrue();
            expect($result->payload)->toBeNull();
            expect($result->parseError)->toContain('exit code 2');
            expect($result->parseError)->toContain('boom');
        });
    } finally {
        removeRuntimeTestDir($root);
    }
});

it('reports a timeout before attempting to parse output', function (): void {
    $root = runtimeTestTempDir('timeout');
    $commandsRoot = $root . DIRECTORY_SEPARATOR . 'commands';
    $relativeCommand = 'mcp/test.php';
    writeRuntimeCommandFile($commandsRoot, $relativeCommand);

    $binary = $root . DIRECTORY_SEPARATOR . 'kirby-cli';
    writeRuntimeTestScript($binary, <<<'PHP'
fwrite(STDERR, "slow");
sleep(10);
echo "__KIRBY_MCP_JSON__" . json_encode(['ok' => true]) . "__KIRBY_MCP_JSON_END__";
PHP);

    try {
        withKirbyCliBinary($binary, function () use ($root, $commandsRoot, $relativeCommand): void {
            $runtime = new FakeRuntimeContext($root, $commandsRoot);
            $runner = new RuntimeCommandRunner($runtime, new KirbyCliRunner());

            $result = $runner->runMarkedJson($relativeCommand, ['test'], 5);

            expect($result->installed)->toBeTrue();
            expect($result->payload)->toBeNull();
            expect($result->cliMeta())->toBeArray();
            expect($result->cliMeta()['timedOut'])->toBeTrue();
            expect($result->parseError)->toContain('timed out');
        });
    } finally {
        removeRuntimeTestDir($root);
    }
});

it('reports parse errors and includes stderr output', function (): void {
    $root = runtimeTestTempDir('garbled');
    $commandsRoot = $root . DIRECTORY_SEPARATOR . 'commands';
    $relativeCommand = 'mcp/test.php';
    writeRuntimeCommandFile($commandsRoot, $relativeCommand);

    $binary = $root . DIRECTORY_SEPARATOR . 'kirby-cli';
    writeRuntimeTestScript($binary, <<<'PHP'
fwrite(STDERR, "garbled");
echo "__KIRBY_MCP_JSON__{not json}__KIRBY_MCP_JSON_END__";
PHP);

    try {
        withKirbyCliBinary($binary, function () use ($root, $commandsRoot, $relativeCommand): void {
            $runtime = new FakeRuntimeContext($root, $commandsRoot);
            $runner = new RuntimeCommandRunner($runtime, new KirbyCliRunner());

            $result = $runner->runMarkedJson($relativeCommand, ['test'], 5);

            expect($result->installed)->toBeTrue();
            expect($result->payload)->toBeNull();
            expect($result->parseError)->toContain('Failed to parse JSON string');
            expect($result->parseError)->toContain('garbled');
        });
    } finally {
        removeRuntimeTestDir($root);
    }
});
