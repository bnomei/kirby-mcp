<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\OAuth;

final readonly class OAuthFileStore
{
    public function __construct(
        private string $root,
    ) {
    }

    public function root(): string
    {
        return $this->root;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function write(string $bucket, string $id, array $data): void
    {
        $path = $this->path($bucket, $id);
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $tmp = $path . '.' . bin2hex(random_bytes(6)) . '.tmp';
        $encoded = json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (file_put_contents($tmp, $encoded . PHP_EOL, LOCK_EX) === false) {
            throw new \RuntimeException('Failed to write OAuth store file.');
        }

        if (!rename($tmp, $path)) {
            @unlink($tmp);
            throw new \RuntimeException('Failed to move OAuth store file into place.');
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function read(string $bucket, string $id): ?array
    {
        $path = $this->path($bucket, $id);
        if (!is_file($path)) {
            return null;
        }

        $contents = file_get_contents($path);
        if (!is_string($contents)) {
            return null;
        }

        try {
            $decoded = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Atomically claim and remove a record. Only the first concurrent caller
     * receives the data; callers that lose the race get null. `rename()` is
     * atomic on POSIX filesystems, so exactly one process can move the file out
     * — this makes single-use records (e.g. OAuth authorization codes) safe
     * against concurrent read-then-delete races.
     *
     * @return array<string, mixed>|null
     */
    public function take(string $bucket, string $id): ?array
    {
        $path = $this->path($bucket, $id);
        if (!is_file($path)) {
            return null;
        }

        $claim = $path . '.' . bin2hex(random_bytes(6)) . '.claim';
        if (!@rename($path, $claim)) {
            // The file vanished or another caller claimed it first.
            return null;
        }

        $contents = @file_get_contents($claim);
        @unlink($claim);

        if (!is_string($contents)) {
            return null;
        }

        try {
            $decoded = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }

    public function delete(string $bucket, string $id): void
    {
        $path = $this->path($bucket, $id);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    public function path(string $bucket, string $id): string
    {
        $bucket = $this->filePart($bucket);

        return rtrim($this->root, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR . $bucket
            . DIRECTORY_SEPARATOR . $this->filePart($id) . '.json';
    }

    private function filePart(string $value): string
    {
        $value = trim($value);
        if ($value !== '' && preg_match('/^[A-Za-z0-9._-]+$/', $value) === 1) {
            return $value;
        }

        return hash('sha256', $value);
    }
}
