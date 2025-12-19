<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Support;

use Bnomei\KirbyMcp\Project\KirbyRootsInspectionResult;

final readonly class KirbyRootsInspectionCacheEntry
{
    public function __construct(
        public KirbyRootsInspectionResult $inspection,
        public int $inspectedAt,
        public ?string $indexPhpPath,
        public ?int $indexPhpMtime,
    ) {
    }
}
