<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Dumps;

final class McpDump
{
    private const COLORS = ['green', 'orange', 'red', 'purple', 'blue', 'gray'];

    private const INTERNAL_CLASS_PREFIXES = [
        'Bnomei\\KirbyMcp\\Dumps\\',
    ];

    private const INTERNAL_FUNCTIONS = [
        'mcp_dump',
    ];

    /**
     * @param array<int, mixed> $values
     * @param array<string, mixed>|null $origin
     */
    private function __construct(
        private readonly string $traceId,
        private readonly string $id,
        private readonly ?string $path,
        private readonly ?string $method,
        private readonly ?string $url,
        private readonly ?string $projectRoot,
        private readonly array $values,
        private readonly ?array $origin,
    ) {
        $this->write([
            'type' => 'dump',
            't' => microtime(true),
            'traceId' => $this->traceId,
            'id' => $this->id,
            'path' => $this->path,
            'method' => $this->method,
            'url' => $this->url,
            'origin' => $this->origin,
            'values' => array_map(
                static fn (mixed $value): mixed => DumpValueNormalizer::normalize($value),
                $this->values,
            ),
        ]);
    }

    /**
     * @param array<int, mixed> $values
     */
    public static function create(array $values = [], ?array $backtrace = null, ?string $projectRoot = null): self
    {
        $traceId = McpDumpContext::traceId();
        $id = self::newDumpId();

        $path = McpDumpContext::path();
        $method = McpDumpContext::method();
        $url = McpDumpContext::url();

        $origin = self::originFromBacktrace($backtrace);

        return new self(
            traceId: $traceId,
            id: $id,
            path: $path,
            method: $method,
            url: $url,
            projectRoot: $projectRoot,
            values: $values,
            origin: $origin,
        );
    }

    public function label(string $label): self
    {
        $label = trim($label);
        if ($label === '') {
            return $this;
        }

        return $this->update(['label' => $label]);
    }

    public function green(): self
    {
        return $this->color('green');
    }

    public function orange(): self
    {
        return $this->color('orange');
    }

    public function red(): self
    {
        return $this->color('red');
    }

    public function purple(): self
    {
        return $this->color('purple');
    }

    public function blue(): self
    {
        return $this->color('blue');
    }

    public function gray(): self
    {
        return $this->color('gray');
    }

    public function caller(): self
    {
        $caller = self::callerFromBacktrace(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 12));
        if ($caller === null) {
            return $this;
        }

        return $this->update(['caller' => $caller]);
    }

    public function pass(mixed $value): mixed
    {
        $this->update([
            'values' => [DumpValueNormalizer::normalize($value)],
        ]);

        return $value;
    }

    public function trace(int $maxFrames = 25): self
    {
        $maxFrames = max(1, min(100, $maxFrames));
        $frames = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $maxFrames + 5);

        $trace = [];
        foreach ($frames as $frame) {
            if (!is_array($frame)) {
                continue;
            }

            if (self::isInternalFrame($frame)) {
                continue;
            }

            $trace[] = [
                'file' => $frame['file'] ?? null,
                'line' => $frame['line'] ?? null,
                'function' => $frame['function'] ?? null,
                'class' => $frame['class'] ?? null,
                'type' => $frame['type'] ?? null,
            ];

            if (count($trace) >= $maxFrames) {
                break;
            }
        }

        return $this->update(['trace' => $trace]);
    }

    public function backtrace(int $maxFrames = 25): self
    {
        return $this->trace($maxFrames);
    }

    public function traceId(): string
    {
        return $this->traceId;
    }

    public function id(): string
    {
        return $this->id;
    }

    private function color(string $color): self
    {
        $color = strtolower(trim($color));
        if (!in_array($color, self::COLORS, true)) {
            return $this;
        }

        return $this->update(['color' => $color]);
    }

    /**
     * @param array<string, mixed> $set
     */
    private function update(array $set): self
    {
        $this->write([
            'type' => 'update',
            't' => microtime(true),
            'traceId' => $this->traceId,
            'id' => $this->id,
            'path' => $this->path,
            'set' => $set,
        ]);

        return $this;
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function write(array $entry): void
    {
        try {
            DumpLogWriter::append($entry, $this->projectRoot);
        } catch (\Throwable) {
            // never break rendering because of debug output
        }
    }

    private static function newDumpId(): string
    {
        try {
            return bin2hex(random_bytes(12));
        } catch (\Throwable) {
            return 'dump_' . substr(sha1((string) microtime(true)), 0, 20);
        }
    }

    /**
     * @param array<int, array<string, mixed>>|null $backtrace
     * @return array<string, mixed>|null
     */
    private static function originFromBacktrace(?array $backtrace): ?array
    {
        if (!is_array($backtrace)) {
            return null;
        }

        foreach ($backtrace as $frame) {
            if (!is_array($frame)) {
                continue;
            }

            if (self::isInternalFrame($frame)) {
                continue;
            }

            return [
                'file' => $frame['file'] ?? null,
                'line' => $frame['line'] ?? null,
                'function' => $frame['function'] ?? null,
                'class' => $frame['class'] ?? null,
                'type' => $frame['type'] ?? null,
            ];
        }

        return null;
    }

    /**
     * @param array<int, array<string, mixed>> $backtrace
     * @return array<string, mixed>|null
     */
    private static function callerFromBacktrace(array $backtrace): ?array
    {
        foreach ($backtrace as $frame) {
            if (!is_array($frame)) {
                continue;
            }

            if (self::isInternalFrame($frame)) {
                continue;
            }

            return [
                'file' => $frame['file'] ?? null,
                'line' => $frame['line'] ?? null,
                'function' => $frame['function'] ?? null,
                'class' => $frame['class'] ?? null,
                'type' => $frame['type'] ?? null,
            ];
        }

        return null;
    }

    /**
     * @param array<string, mixed> $frame
     */
    private static function isInternalFrame(array $frame): bool
    {
        $function = $frame['function'] ?? null;
        if (is_string($function) && $function !== '' && in_array($function, self::INTERNAL_FUNCTIONS, true)) {
            return true;
        }

        $class = $frame['class'] ?? null;
        if (is_string($class) && $class !== '') {
            foreach (self::INTERNAL_CLASS_PREFIXES as $prefix) {
                if (str_starts_with($class, $prefix)) {
                    return true;
                }
            }
        }

        $file = $frame['file'] ?? null;
        if (is_string($file) && $file !== '') {
            $normalized = str_replace('\\', '/', $file);
            if (str_ends_with($normalized, '/src/mcp-dump.php')) {
                return true;
            }

            if (str_contains($normalized, '/src/Dumps/')) {
                return true;
            }
        }

        return false;
    }
}
