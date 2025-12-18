<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Blueprint;

final class BlueprintScanner
{
    public function __construct(
        private readonly BlueprintYaml $yaml = new BlueprintYaml(),
    ) {
    }

    public function scan(string $projectRoot, ?string $blueprintsRoot = null): BlueprintScanResult
    {
        $projectRoot = rtrim($projectRoot, DIRECTORY_SEPARATOR);
        if (!is_string($blueprintsRoot) || trim($blueprintsRoot) === '') {
            $blueprintsRoot = $projectRoot . '/site/blueprints';
        }

        return $this->scanAt($projectRoot, $blueprintsRoot);
    }

    public function scanAt(string $projectRoot, string $blueprintsRoot): BlueprintScanResult
    {
        $projectRoot = rtrim($projectRoot, DIRECTORY_SEPARATOR);
        $blueprintsRoot = rtrim($blueprintsRoot, DIRECTORY_SEPARATOR);

        if (!is_dir($blueprintsRoot)) {
            return new BlueprintScanResult(
                projectRoot: $projectRoot,
                blueprintsRoot: $blueprintsRoot,
                blueprints: [],
                errors: [
                    ['path' => $blueprintsRoot, 'error' => 'Blueprints directory not found'],
                ],
            );
        }

        $errors = [];
        $blueprints = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($blueprintsRoot, \FilesystemIterator::SKIP_DOTS),
        );

        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $filename = $file->getFilename();
            if (!str_ends_with($filename, '.yml') && !str_ends_with($filename, '.yaml')) {
                continue;
            }

            $absolutePath = $file->getRealPath() ?: $file->getPathname();
            $relativePath = ltrim(str_replace($blueprintsRoot, '', $absolutePath), DIRECTORY_SEPARATOR);
            $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);

            $id = preg_replace('/\\.(yml|yaml)$/', '', $relativePath);
            if (!is_string($id) || $id === '') {
                $errors[] = ['path' => $absolutePath, 'error' => 'Failed to derive blueprint id'];
                continue;
            }

            $type = $this->inferTypeFromId($id);

            $data = null;
            try {
                $data = $this->yaml->parseFile($absolutePath);
            } catch (\RuntimeException $exception) {
                $errors[] = ['path' => $absolutePath, 'error' => $exception->getMessage()];
            }

            $blueprints[$id] = new BlueprintFile(
                id: $id,
                type: $type,
                absolutePath: $absolutePath,
                relativePath: $relativePath,
                data: $data,
            );
        }

        ksort($blueprints);

        return new BlueprintScanResult(
            projectRoot: $projectRoot,
            blueprintsRoot: $blueprintsRoot,
            blueprints: $blueprints,
            errors: $errors,
        );
    }

    private function inferTypeFromId(string $id): BlueprintType
    {
        if ($id === 'site') {
            return BlueprintType::Site;
        }

        return match (strtok($id, '/')) {
            'pages' => BlueprintType::Page,
            'files' => BlueprintType::File,
            'users' => BlueprintType::User,
            'fields' => BlueprintType::Field,
            'sections' => BlueprintType::Section,
            'blocks' => BlueprintType::Block,
            default => BlueprintType::Unknown,
        };
    }
}
