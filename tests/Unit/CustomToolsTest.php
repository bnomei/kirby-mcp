<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\CustomTools;
use Bnomei\KirbyMcp\Mcp\Permissions;
use Bnomei\KirbyMcp\Mcp\Resources\MetaResources;
use Bnomei\KirbyMcp\Mcp\ServerProfile;
use Bnomei\KirbyMcp\Project\KirbyMcpConfig;
use Mcp\Capability\Registry;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\Request\CallToolRequest;
use Mcp\Server\RequestContext;
use Mcp\Server\Session\InMemorySessionStore;
use Mcp\Server\Session\Session;

beforeEach(function (): void {
    $this->previousRoot = getenv('KIRBY_MCP_PROJECT_ROOT');
    $this->root = sys_get_temp_dir() . '/kirby-custom-' . bin2hex(random_bytes(8));
    mkdir($this->root . '/.kirby-mcp', 0777, true);
    putenv('KIRBY_MCP_PROJECT_ROOT=' . $this->root);
});

afterEach(function (): void {
    @unlink($this->root . '/.kirby-mcp/mcp.json');
    rmdir($this->root . '/.kirby-mcp');
    rmdir($this->root);
    putenv($this->previousRoot === false ? 'KIRBY_MCP_PROJECT_ROOT' : 'KIRBY_MCP_PROJECT_ROOT=' . $this->previousRoot);
    KirbyMcpConfig::clearCache();
});

it('keeps custom discovery opt-in, project-local and subject to CLI permissions', function (): void {
    expect(CustomTools::configured())->toBe([]);
    file_put_contents($this->root . '/.kirby-mcp/mcp.json', json_encode(['customTools' => [
        'custom_report' => ['command' => 'report', 'description' => 'Read a report', 'readOnly' => true],
    ]], JSON_THROW_ON_ERROR));
    KirbyMcpConfig::clearCache();
    $registry = new Registry();
    CustomTools::configured()[0]->register($registry);
    $allowed = new Permissions(['access' => true, 'execution' => true, 'execution.cli' => true]);
    $denied = new Permissions(['access' => true, 'execution' => false, 'execution.cli' => true]);
    expect($allowed->request(['method' => 'tools/call', 'params' => ['name' => 'custom_report']]))->toBeTrue()
        ->and($denied->request(['method' => 'tools/call', 'params' => ['name' => 'custom_report']]))->toBeFalse()
        ->and(array_column((new MetaResources(permissions: $allowed))->toolIndex(), 'name'))->toContain('custom_report')
        ->and(array_column((new MetaResources(permissions: $denied))->toolIndex(), 'name'))->not->toContain('custom_report')
        ->and(array_column((new MetaResources(ServerProfile::GLOBAL_REFERENCE))->toolIndex(), 'name'))->not->toContain('custom_report');
    $allowed->filterRegistry($registry);
    expect($registry->hasTool('custom_report'))->toBeTrue();
    $denied->filterRegistry($registry);
    expect($registry->hasTool('custom_report'))->toBeFalse();
});

it('guards writes and required inputs independently of SDK schema validation', function (array $arguments, string $error): void {
    file_put_contents($this->root . '/.kirby-mcp/mcp.json', json_encode(['customTools' => [
        'custom_write' => ['command' => 'report', 'description' => 'Write a report',
            'inputSchema' => ['type' => 'object', 'properties' => ['count' => ['type' => 'integer', 'default' => 7]], 'required' => ['count']]],
    ]], JSON_THROW_ON_ERROR));
    $registry = new Registry();
    CustomTools::configured()[0]->register($registry);
    $handler = $registry->getTool('custom_write')->handler;
    if (!is_callable($handler)) {
        throw new LogicException('Expected a callable custom tool handler.');
    }
    $context = new RequestContext(new Session(new InMemorySessionStore(60)), new CallToolRequest('custom_write', $arguments));
    expect(fn () => $handler($context))->toThrow(ToolCallException::class, $error);
})->with([
    'missing opt-in' => [['count' => 7], 'require allowWrite=true'],
    'false opt-in' => [['count' => 7, 'allowWrite' => false], 'require allowWrite=true'],
    'default does not satisfy required input' => [['allowWrite' => true], 'Required CLI input: count'],
    'raw type still checked' => [['allowWrite' => true, 'count' => 'seven'], 'Invalid type for CLI input: count'],
]);

it('rejects invalid custom definitions instead of exposing a partial or ambiguous tool', function (string $name, array $overrides): void {
    $definition = array_replace(['command' => 'report', 'description' => 'Read a report'], $overrides);
    file_put_contents($this->root . '/.kirby-mcp/mcp.json', json_encode(['customTools' => [$name => $definition]], JSON_THROW_ON_ERROR));
    expect(fn () => CustomTools::configured())->toThrow(InvalidArgumentException::class);
})->with([
    'core name' => ['kirby_info', []],
    'runtime bypass' => ['custom_eval', ['command' => 'mcp:eval']],
    'shell string' => ['custom_report', ['command' => 'report --all']],
    'write declaration typo' => ['custom_report', ['readOnly' => 'false']],
    'draft-07 reference bypass' => ['custom_report', ['inputSchema' => ['$schema' => 'http://json-schema.org/draft-07/schema#', '$ref' => '#/definitions/base', 'definitions' => ['base' => ['type' => 'object']], 'type' => 'object', 'properties' => []]]],
    'property reference bypass' => ['custom_report', ['inputSchema' => ['type' => 'object', 'properties' => ['count' => ['type' => 'integer', '$ref' => '#/definitions/anything']]]]],
    'short fixed alias' => ['custom_report', ['arguments' => ['-k=trusted'], 'inputSchema' => ['type' => 'object', 'properties' => ['key' => ['type' => 'string']]]]],
    'lossy object input' => ['custom_report', ['inputSchema' => ['type' => 'object', 'properties' => ['data' => ['type' => 'object']]]]],
    'reserved input' => ['custom_report', ['inputSchema' => ['type' => 'object', 'properties' => ['allowWrite' => ['type' => 'boolean']]]]],
    'fixed target override' => ['custom_report', ['arguments' => ['--key=trusted'], 'inputSchema' => ['type' => 'object', 'properties' => ['key' => ['type' => 'string']]]]],
]);
