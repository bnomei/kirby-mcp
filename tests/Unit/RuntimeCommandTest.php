<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\Support\JsonMarkers;
use Bnomei\KirbyMcp\Mcp\Support\RuntimeCommand;
use Kirby\CLI\CLI;
use Kirby\Cms\App;

final class RuntimeCommandProbe extends RuntimeCommand
{
    public static function definition(): array
    {
        return [];
    }

    public static function run(CLI $cli): void
    {
    }

    public static function kirbyOrEmitErrorPublic(CLI $cli): ?App
    {
        return parent::kirbyOrEmitError($cli);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function emitPublic(CLI $cli, array $payload): void
    {
        parent::emit($cli, $payload);
    }

    public static function traceForCliPublic(CLI $cli, Throwable $exception, int $maxChars = 20000): ?string
    {
        return parent::traceForCli($cli, $exception, $maxChars);
    }

    /**
     * @return array{class: string, message: string, code: int, trace?: string}
     */
    public static function errorArrayPublic(Throwable $exception, ?string $trace = null): array
    {
        return parent::errorArray($exception, $trace);
    }
}

final class RuntimeCommandTestCli extends CLI
{
    /**
     * @param array<string, mixed> $args
     */
    public function __construct(
        private array $args = [],
        ?App $kirby = null,
    ) {
        $this->kirby = $kirby;
    }

    public function arg(string $name): mixed
    {
        return $this->args[$name] ?? null;
    }

    public function kirby(bool $fail = true): ?App
    {
        return $this->kirby;
    }

    public function json(array $data = []): string
    {
        return json_encode($data, JSON_UNESCAPED_SLASHES);
    }
}

final class RuntimeCommandTestApp extends App
{
    public function __construct()
    {
    }
}

it('emits an error payload when no kirby instance is available', function (): void {
    $cli = new RuntimeCommandTestCli();

    ob_start();
    $result = RuntimeCommandProbe::kirbyOrEmitErrorPublic($cli);
    $output = ob_get_clean();

    expect($result)->toBeNull();
    expect($output)->toContain(JsonMarkers::START);
    expect($output)->toContain(JsonMarkers::END);
    expect($output)->toContain('The Kirby installation could not be found.');
});

it('returns the kirby instance without output when available', function (): void {
    $kirby = new RuntimeCommandTestApp();
    $cli = new RuntimeCommandTestCli(kirby: $kirby);

    ob_start();
    $result = RuntimeCommandProbe::kirbyOrEmitErrorPublic($cli);
    $output = ob_get_clean();

    expect($result)->toBe($kirby);
    expect($output)->toBe('');
});

it('formats error payloads and respects debug trace settings', function (): void {
    $exception = new RuntimeException('boom', 123);

    $payload = RuntimeCommandProbe::errorArrayPublic($exception);
    expect($payload)->toBe([
        'class' => RuntimeException::class,
        'message' => 'boom',
        'code' => 123,
    ]);

    $payloadWithTrace = RuntimeCommandProbe::errorArrayPublic($exception, 'trace');
    expect($payloadWithTrace['trace'])->toBe('trace');

    $cliNoDebug = new RuntimeCommandTestCli(['debug' => false]);
    expect(RuntimeCommandProbe::traceForCliPublic($cliNoDebug, $exception, 1))->toBeNull();

    $cliDebug = new RuntimeCommandTestCli(['debug' => true]);
    $trace = RuntimeCommandProbe::traceForCliPublic($cliDebug, $exception, 1);
    expect($trace)->toBeString();
    assert(is_string($trace));
    expect(strlen($trace))->toBeLessThanOrEqual(1);
});
