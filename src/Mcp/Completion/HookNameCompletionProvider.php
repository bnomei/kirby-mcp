<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Completion;

use Bnomei\KirbyMcp\Docs\HookReferenceIndex;
use Mcp\Capability\Completion\ProviderInterface;

final class HookNameCompletionProvider implements ProviderInterface
{
    public function getCompletions(string $currentValue): array
    {
        $currentValue = strtolower(trim($currentValue));

        $hooks = HookReferenceIndex::HOOKS;

        if ($currentValue !== '') {
            $hooks = array_values(array_filter($hooks, static function (string $hook) use ($currentValue): bool {
                $canonical = strtolower($hook);
                $slug = strtolower(str_replace(['.', ':'], '-', $hook));

                return str_contains($canonical, $currentValue) || str_contains($slug, $currentValue);
            }));
        }

        $hooks = array_values(array_unique($hooks));
        sort($hooks);

        return $hooks;
    }
}
