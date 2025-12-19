<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Project;

use Bnomei\KirbyMcp\Cli\KirbyCliResult;

final readonly class KirbyRootsInspectionResult
{
    public function __construct(
        public KirbyRoots $roots,
        public KirbyCliResult $cliResult,
    ) {
    }
}
