<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Completion;

use Bnomei\KirbyMcp\Docs\PanelReferenceIndex;
use Mcp\Capability\Completion\ProviderInterface;

final class PanelSectionTypeCompletionProvider implements ProviderInterface
{
    public function getCompletions(string $currentValue): array
    {
        $currentValue = strtolower(trim($currentValue));

        $types = array_keys(PanelReferenceIndex::SECTION_TYPES);

        if ($currentValue !== '') {
            $types = array_values(array_filter($types, static fn (string $type): bool => str_contains(strtolower($type), $currentValue)));
        }

        $types = array_values(array_unique($types));
        sort($types);

        return $types;
    }
}
