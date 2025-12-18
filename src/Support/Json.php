<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Support;

use JsonException;

final class Json
{
    /**
     * @return array<mixed>
     */
    public static function decodeString(string $json): array
    {
        try {
            /** @var array<mixed> */
            return json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new \RuntimeException('Failed to parse JSON string', previous: $exception);
        }
    }

    /**
     * @return array<mixed>
     */
    public static function decodeFile(string $path): array
    {
        if (!is_file($path)) {
            throw new \RuntimeException("JSON file not found: {$path}");
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new \RuntimeException("Failed to read JSON file: {$path}");
        }

        try {
            /** @var array<mixed> */
            return json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new \RuntimeException("Failed to parse JSON file: {$path}", previous: $exception);
        }
    }
}
