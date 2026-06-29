<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Cli\KirbyCliRunner;
use Bnomei\KirbyMcp\Mcp\Support\KirbyRuntimeContext;

it('does not cache a failed roots inspection and retries on the next call', function (): void {
    $projectRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kirby-mcp-roots-' . bin2hex(random_bytes(8));
    mkdir($projectRoot, 0777, true);

    $failStub = $projectRoot . DIRECTORY_SEPARATOR . 'fail.php';
    file_put_contents($failStub, "<?php\nfwrite(STDERR, 'boom');\nexit(1);\n");

    $okStub = $projectRoot . DIRECTORY_SEPARATOR . 'ok.php';
    file_put_contents(
        $okStub,
        "<?php\necho \"[\\\"index\\\"] =>\\n\";\necho \"string(18) \\\"/custom/index/root\\\"\\n\";\nexit(0);\n"
    );

    $previousRoot = getenv('KIRBY_MCP_PROJECT_ROOT');
    putenv('KIRBY_MCP_PROJECT_ROOT=' . $projectRoot);
    KirbyRuntimeContext::clearRootsCache();

    try {
        putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . $failStub);
        $first = (new KirbyRuntimeContext())->rootsInspection();
        expect($first->cliResult->exitCode)->not()->toBe(0);
        expect($first->roots->toArray())->toBe([]);

        putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . $okStub);
        $second = (new KirbyRuntimeContext())->rootsInspection();
        expect($second->cliResult->exitCode)->toBe(0);
        expect($second->roots->get('index'))->toBe('/custom/index/root');
    } finally {
        KirbyRuntimeContext::clearRootsCache();
        putenv(KirbyCliRunner::ENV_KIRBY_BIN);
        if (is_string($previousRoot) && $previousRoot !== '') {
            putenv('KIRBY_MCP_PROJECT_ROOT=' . $previousRoot);
        } else {
            putenv('KIRBY_MCP_PROJECT_ROOT');
        }
        @unlink($failStub);
        @unlink($okStub);
        @rmdir($projectRoot);
    }
});
