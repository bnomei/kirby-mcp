<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Resources;

use Mcp\Capability\Attribute\McpResourceTemplate;

final class SusieResources
{
    /**
     * Boss battle attack loop orders (v1) from the WiKirby Susie page.
     *
     * Source: https://wikirby.com/wiki/Susie#Boss_Battle (action=raw)
     *
     * @var array<int, list<string>>
     */
    private const ATTACKS_V1 = [
        1 => [
            'Driver Slam',
            'Driver Slam',
            'Spin Cycle Dash',
            'Driver Missile (from center of arena)',
            'Spin Cycle',
            'Driver Slam',
            'Driver Slam',
            'Driver Missile (from side of arena)',
            'Driver Slam',
            'Driver Missile (from center of arena)',
            'Spin Cycle',
            'Driver Missile (from side of arena)',
            'Driver Slam',
            'Spin Cycle Dash',
        ],
        2 => [
            'Drillbit',
            'Drillbit (launched from Business Suit) x2',
            'Tower Spin Cycle',
            'Driver Slam',
            'Drillbit (launched from Business Suit)',
            'Drillbit (launched from Business Suit)',
            'Tower Spin Cycle',
            'Drillbit',
            'Drillbit (launched from Business Suit) x2',
            'Driver Slam',
            'Tower Spin Cycle',
        ],
        3 => [
            'Tower Strike',
            'Tower Strike',
            'Drillbit x2',
            'Tower Strike Bolt',
            'Tower Strike Bolt',
            'Tower Strike Bolt',
            'Drillbit x2',
            'Drillbit x2',
            'Tower Strike Bolt',
            'Tower Strike Bolt',
            'Tower Strike Bolt',
            'Drillbit',
            'Tower Strike',
        ],
    ];

    /**
     * Easter egg resource template.
     *
     * Provide a `phase` and `step` (1-based) and get Susie's attack name from the boss battle loop order (v1).
     *
     * @return array{
     *   nickname: 'Susie',
     *   version: 1,
     *   phase: int,
     *   step: int,
     *   attack: string|null
     * }
     */
    #[McpResourceTemplate(
        uriTemplate: 'kirby://susie/{phase}/{step}',
        name: 'susie',
        description: 'Easter egg: Susie (Kirby: Planet Robobot) boss attack loop. Provide phase (1-3) and step (1-based). Returns attack name or null.',
        mimeType: 'application/json',
    )]
    public function susie(int $phase, int $step): array
    {
        $attacks = self::ATTACKS_V1[$phase] ?? null;

        $attack = null;
        if (is_array($attacks) && $step >= 1 && $step <= count($attacks)) {
            $attack = $attacks[$step - 1] ?? null;
        }

        return [
            'nickname' => 'Susie',
            'version' => 1,
            'phase' => $phase,
            'step' => $step,
            'attack' => $attack,
        ];
    }
}
