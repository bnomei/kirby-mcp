<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Project;

final readonly class KirbyMcpConfig
{
    /**
     * @param array<mixed> $data
     */
    private function __construct(
        public ?string $path,
        public array $data,
        public ?string $error = null,
    ) {
    }

    public static function load(string $projectRoot): self
    {
        $dir = rtrim($projectRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '.kirby-mcp';

        $candidates = [
            $dir . DIRECTORY_SEPARATOR . 'mcp.json',
            $dir . DIRECTORY_SEPARATOR . 'config.json',
        ];

        foreach ($candidates as $path) {
            if (!is_file($path)) {
                continue;
            }

            $contents = file_get_contents($path);
            if (!is_string($contents)) {
                return new self($path, [], 'Failed to read config file.');
            }

            $contents = trim($contents);
            if ($contents === '') {
                return new self($path, [], 'Config file is empty.');
            }

            try {
                /** @var mixed $decoded */
                $decoded = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
            } catch (\JsonException $exception) {
                return new self($path, [], 'Invalid JSON in config file: ' . $exception->getMessage());
            }

            if (!is_array($decoded)) {
                return new self($path, [], 'Config JSON must be an object.');
            }

            /** @var array<mixed> $decoded */
            return new self($path, $decoded);
        }

        return new self(null, []);
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

        $host = $this->data['kirbyHost'] ?? null;
        if (is_string($host) && trim($host) !== '') {
            return trim($host);
        }

        return null;
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
