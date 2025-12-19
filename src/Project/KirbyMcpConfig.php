<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Project;

final readonly class KirbyMcpConfig
{
    public const DEFAULT_CACHE_TTL_SECONDS = 60;
    public const DEFAULT_DOCS_TTL_SECONDS = 86400; // 1 day
    public const DEFAULT_DUMPS_MAX_BYTES = 2097152; // 2 MB
    public const DEFAULT_DUMPS_ENABLED = true;
    public const DEFAULT_IDE_TYPE_HINT_SCAN_BYTES = 16384; // 16 KB

    /**
     * @param array<mixed> $data
     */
    private function __construct(
        public ?string $path,
        public array $data,
        public ?string $error = null,
    ) {
    }

    /**
     * @return array<string, array{stamp:string, config:self}>
     */
    private static function &cache(): array
    {
        /** @var array<string, array{stamp:string, config:self}> $cache */
        static $cache = [];

        return $cache;
    }

    public static function clearCache(): int
    {
        $cache = &self::cache();
        $count = count($cache);
        $cache = [];

        return $count;
    }

    public static function load(string $projectRoot): self
    {
        /** @var array<string, array{stamp:string, config:self}> $cache */
        $cache = &self::cache();

        $dir = rtrim($projectRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '.kirby-mcp';

        $candidates = [
            $dir . DIRECTORY_SEPARATOR . 'mcp.json',
            $dir . DIRECTORY_SEPARATOR . 'config.json',
        ];

        $selectedPath = null;
        foreach ($candidates as $path) {
            if (is_file($path)) {
                $selectedPath = $path;
                break;
            }
        }

        $cacheKey = rtrim($projectRoot, DIRECTORY_SEPARATOR);

        if (is_string($selectedPath) && $selectedPath !== '') {
            $mtime = filemtime($selectedPath);
            $stamp = $selectedPath . '|' . (is_int($mtime) ? $mtime : 0);
        } else {
            $dirMtime = is_dir($dir) ? filemtime($dir) : false;
            $stamp = 'none|' . (is_int($dirMtime) ? $dirMtime : 0);
        }

        $cached = $cache[$cacheKey] ?? null;
        if (is_array($cached) && ($cached['stamp'] ?? null) === $stamp && ($cached['config'] ?? null) instanceof self) {
            return $cached['config'];
        }

        if (is_string($selectedPath) && $selectedPath !== '') {
            $contents = file_get_contents($selectedPath);
            if (!is_string($contents)) {
                $config = new self($selectedPath, [], 'Failed to read config file.');
                $cache[$cacheKey] = ['stamp' => $stamp, 'config' => $config];
                return $config;
            }

            $contents = trim($contents);
            if ($contents === '') {
                $config = new self($selectedPath, [], 'Config file is empty.');
                $cache[$cacheKey] = ['stamp' => $stamp, 'config' => $config];
                return $config;
            }

            try {
                /** @var mixed $decoded */
                $decoded = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
            } catch (\JsonException $exception) {
                $config = new self($selectedPath, [], 'Invalid JSON in config file: ' . $exception->getMessage());
                $cache[$cacheKey] = ['stamp' => $stamp, 'config' => $config];
                return $config;
            }

            if (!is_array($decoded)) {
                $config = new self($selectedPath, [], 'Config JSON must be an object.');
                $cache[$cacheKey] = ['stamp' => $stamp, 'config' => $config];
                return $config;
            }

            /** @var array<mixed> $decoded */
            $config = new self($selectedPath, $decoded);
            $cache[$cacheKey] = ['stamp' => $stamp, 'config' => $config];
            return $config;
        }

        $config = new self(null, []);
        $cache[$cacheKey] = ['stamp' => $stamp, 'config' => $config];
        return $config;
    }

    /**
     * @return array<int, string>
     */
    public function cliAllow(): array
    {
        return $this->stringList($this->data['cli']['allow'] ?? null);
    }

    /**
     * @return array<int, string>
     */
    public function cliAllowWrite(): array
    {
        return $this->stringList($this->data['cli']['allowWrite'] ?? null);
    }

    /**
     * @return array<int, string>
     */
    public function cliDeny(): array
    {
        return $this->stringList($this->data['cli']['deny'] ?? null);
    }

    public function kirbyHost(): ?string
    {
        $kirby = $this->data['kirby'] ?? null;
        if (is_array($kirby)) {
            $host = $kirby['host'] ?? null;
            if (is_string($host) && trim($host) !== '') {
                return trim($host);
            }
        }

        return null;
    }

    public function evalEnabled(): bool
    {
        $eval = $this->data['eval'] ?? null;
        if (is_array($eval)) {
            $enabled = $eval['enabled'] ?? null;
            if ($enabled === true) {
                return true;
            }
        }

        return false;
    }

    public function cacheTtlSeconds(): int
    {
        $cache = $this->data['cache'] ?? null;
        if (!is_array($cache)) {
            return self::DEFAULT_CACHE_TTL_SECONDS;
        }

        $ttl = $cache['ttlSeconds'] ?? $cache['ttl'] ?? null;

        if (is_string($ttl) && trim($ttl) !== '' && ctype_digit(trim($ttl))) {
            $ttl = (int) trim($ttl);
        }

        if (!is_int($ttl)) {
            return self::DEFAULT_CACHE_TTL_SECONDS;
        }

        return max(0, min(3600, $ttl));
    }

    public function docsTtlSeconds(): int
    {
        $docs = $this->data['docs'] ?? null;
        if (!is_array($docs)) {
            return self::DEFAULT_DOCS_TTL_SECONDS;
        }

        $ttl = $docs['ttlSeconds'] ?? $docs['ttl'] ?? null;

        if (is_string($ttl) && trim($ttl) !== '' && ctype_digit(trim($ttl))) {
            $ttl = (int) trim($ttl);
        }

        if (!is_int($ttl)) {
            return self::DEFAULT_DOCS_TTL_SECONDS;
        }

        return max(0, min(604800, $ttl)); // max 7 days
    }

    public function dumpsMaxBytes(): int
    {
        $dumps = $this->data['dumps'] ?? null;
        if (!is_array($dumps)) {
            return self::DEFAULT_DUMPS_MAX_BYTES;
        }

        $maxBytes = $dumps['maxBytes'] ?? $dumps['maxbytes'] ?? $dumps['max_bytes'] ?? null;

        if (is_string($maxBytes) && trim($maxBytes) !== '' && ctype_digit(trim($maxBytes))) {
            $maxBytes = (int) trim($maxBytes);
        }

        if (!is_int($maxBytes)) {
            return self::DEFAULT_DUMPS_MAX_BYTES;
        }

        return max(0, $maxBytes);
    }

    public function dumpsEnabled(): bool
    {
        $dumps = $this->data['dumps'] ?? null;
        if (!is_array($dumps)) {
            return self::DEFAULT_DUMPS_ENABLED;
        }

        $enabled = $dumps['enabled'] ?? null;
        if (is_bool($enabled)) {
            return $enabled;
        }

        if (is_string($enabled)) {
            $value = strtolower(trim($enabled));
            if ($value === '0' || $value === 'false' || $value === 'off' || $value === 'no') {
                return false;
            }

            if ($value === '1' || $value === 'true' || $value === 'on' || $value === 'yes') {
                return true;
            }
        }

        if (is_int($enabled)) {
            return $enabled !== 0;
        }

        return self::DEFAULT_DUMPS_ENABLED;
    }

    public function ideTypeHintScanBytes(): int
    {
        $ide = $this->data['ide'] ?? null;
        if (!is_array($ide)) {
            return self::DEFAULT_IDE_TYPE_HINT_SCAN_BYTES;
        }

        $bytes = $ide['typeHintScanBytes'] ?? $ide['typeHintsScanBytes'] ?? $ide['scanBytes'] ?? null;

        if (is_string($bytes) && trim($bytes) !== '' && ctype_digit(trim($bytes))) {
            $bytes = (int) trim($bytes);
        }

        if (!is_int($bytes) || $bytes <= 0) {
            return self::DEFAULT_IDE_TYPE_HINT_SCAN_BYTES;
        }

        return max(1024, min(1048576, $bytes));
    }

    /**
     * @return array<int, string>
     */
    private function stringList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $out = [];
        foreach ($value as $item) {
            if (!is_string($item)) {
                continue;
            }

            $item = trim($item);
            if ($item === '') {
                continue;
            }

            $out[] = $item;
        }

        $out = array_values(array_unique($out));
        sort($out);

        return $out;
    }
}
