<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Blueprint;

final readonly class BlueprintScanResult
{
    /**
     * @param array<string, BlueprintFile> $blueprints Keyed by blueprint id
     * @param array<int, array{path:string, error:string}> $errors
     */
    public function __construct(
        public string $projectRoot,
        public string $blueprintsRoot,
        public array $blueprints,
        public array $errors,
    ) {
    }

    /**
     * @return array{
     *   projectRoot: string,
     *   blueprintsRoot: string,
     *   blueprints: array<string, array{
     *     id:string,
     *     type:string,
     *     absolutePath:string,
     *     relativePath:string,
     *     displayName:string,
     *     displayNameSource: 'title'|'name'|'label'|'id',
     *     data: array<mixed>|null
     *   }>,
     *   errors: array<int, array{path:string, error:string}>
     * }
     */
    public function toArray(): array
    {
        $blueprints = [];
        foreach ($this->blueprints as $id => $blueprint) {
            $blueprints[$id] = $blueprint->toArray();
        }

        return [
            'projectRoot' => $this->projectRoot,
            'blueprintsRoot' => $this->blueprintsRoot,
            'blueprints' => $blueprints,
            'errors' => $this->errors,
        ];
    }
}
