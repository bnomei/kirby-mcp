<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\ProjectContext;
use Bnomei\KirbyMcp\Mcp\Resources\ProjectResources;

it('returns composer audit data from the project resource', function (): void {
    $originalRoot = getenv(ProjectContext::ENV_PROJECT_ROOT);
    putenv(ProjectContext::ENV_PROJECT_ROOT . '=' . cmsPath());

    try {
        $resource = new ProjectResources();
        $payload = $resource->composerAudit();

        expect($payload['projectRoot'])->toBe(cmsPath());
        expect($payload['composerJson']['require']['getkirby/cms'])->toBeString();
    } finally {
        if ($originalRoot === false) {
            putenv(ProjectContext::ENV_PROJECT_ROOT);
        } else {
            putenv(ProjectContext::ENV_PROJECT_ROOT . '=' . $originalRoot);
        }
    }
});
