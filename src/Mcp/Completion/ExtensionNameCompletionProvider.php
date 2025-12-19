<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Completion;

use Bnomei\KirbyMcp\Docs\ExtensionReferenceIndex;
use Mcp\Capability\Completion\ProviderInterface;

final class ExtensionNameCompletionProvider implements ProviderInterface
{
    public function getCompletions(string $currentValue): array
    {
        $query = strtolower(trim($currentValue));
        $queryNoDashes = str_replace('-', '', $query);

        $extensions = ExtensionReferenceIndex::EXTENSIONS;
        $slugs = array_keys($extensions);

        if ($query !== '') {
            $slugs = array_values(array_filter($slugs, static function (string $slug) use ($extensions, $query, $queryNoDashes): bool {
                $label = strtolower($extensions[$slug] ?? '');
                $slugNoDashes = str_replace('-', '', strtolower($slug));

                return str_contains(strtolower($slug), $query)
                    || ($queryNoDashes !== '' && str_contains($slugNoDashes, $queryNoDashes))
                    || str_contains($label, $query)
                    || ($queryNoDashes !== '' && str_contains(str_replace([' ', '-', '/'], '', $label), $queryNoDashes));
            }));
        }

        $slugs = array_values(array_unique($slugs));
        sort($slugs);

        return $slugs;
    }
}
