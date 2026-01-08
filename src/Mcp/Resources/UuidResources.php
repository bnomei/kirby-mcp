<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Resources;

use Bnomei\KirbyMcp\Mcp\Attributes\McpToolIndex;
use Bnomei\KirbyMcp\Mcp\ProjectContext;
use Kirby\Cms\App;
use Kirby\Cms\Core;
use Kirby\Uuid\Uuid;
use Mcp\Capability\Attribute\McpResource;
use Mcp\Exception\ResourceReadException;
use Mcp\Schema\Annotations;
use Mcp\Schema\Enum\Role;

final class UuidResources
{
    public function __construct(
        private readonly ProjectContext $context = new ProjectContext(),
    ) {
    }

    #[McpResource(
        uri: 'kirby://uuid/new',
        name: 'uuid_new',
        description: 'Generate a new random Kirby UUID string (respects content.uuid format).',
        mimeType: 'text/plain',
        annotations: new Annotations(
            audience: [Role::Assistant],
            priority: 0.5,
        ),
    )]
    #[McpToolIndex(
        whenToUse: 'Use to generate a fresh Kirby UUID string for new content references or block/layout ids.',
        keywords: [
            'uuid' => 120,
            'generate' => 80,
            'new' => 60,
            'random' => 40,
            'id' => 40,
            'blocks' => 30,
            'layout' => 30,
            'pages' => 20,
            'files' => 20,
        ],
    )]
    public function uuidNew(): string
    {
        if (!class_exists(Uuid::class)) {
            throw new ResourceReadException('Kirby UUID support is not available in this project.');
        }

        if (class_exists(App::class) && class_exists(Core::class) && App::instance(lazy: true) === null && Core::$indexRoot === null) {
            try {
                $projectRoot = $this->context->projectRoot();
                if (is_string($projectRoot) && $projectRoot !== '') {
                    Core::$indexRoot = $projectRoot;
                }
            } catch (\Throwable) {
                // Ignore root detection failures and fall back to Kirby defaults.
            }
        }

        try {
            return Uuid::generate();
        } catch (\Throwable $exception) {
            throw new ResourceReadException($exception->getMessage(), 0, $exception);
        }
    }
}
