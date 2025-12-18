<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Blueprint;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

final class BlueprintYaml
{
    /**
     * @return array<mixed>
     */
    public function parseFile(string $path): array
    {
        try {
            $data = Yaml::parseFile($path);
        } catch (ParseException $exception) {
            throw new \RuntimeException("Failed to parse blueprint YAML: {$path}", previous: $exception);
        }

        if (!is_array($data)) {
            throw new \RuntimeException("Blueprint YAML did not parse to an array: {$path}");
        }

        /** @var array<mixed> $data */
        return $data;
    }
}
