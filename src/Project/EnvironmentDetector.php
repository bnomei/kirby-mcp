<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Project;

final class EnvironmentDetector
{
    public function detect(string $projectRoot): EnvironmentInfo
    {
        $projectRoot = rtrim($projectRoot, DIRECTORY_SEPARATOR);

        $signals = [];

        if (is_file($projectRoot . '/.ddev/config.yaml')) {
            $signals['.ddev/config.yaml'] = 'found';
            return new EnvironmentInfo($projectRoot, 'ddev', $signals);
        }

        if (
            is_file($projectRoot . '/docker-compose.yml')
            || is_file($projectRoot . '/compose.yml')
            || is_file($projectRoot . '/Dockerfile')
        ) {
            if (is_file($projectRoot . '/docker-compose.yml')) {
                $signals['docker-compose.yml'] = 'found';
            }
            if (is_file($projectRoot . '/compose.yml')) {
                $signals['compose.yml'] = 'found';
            }
            if (is_file($projectRoot . '/Dockerfile')) {
                $signals['Dockerfile'] = 'found';
            }
            return new EnvironmentInfo($projectRoot, 'docker', $signals);
        }

        if (is_file($projectRoot . '/.herd.yml')) {
            $signals['.herd.yml'] = 'found';
            return new EnvironmentInfo($projectRoot, 'herd', $signals);
        }

        // Default to plain PHP (we can refine later based on additional signals).
        $signals['default'] = 'no container runner detected';
        return new EnvironmentInfo($projectRoot, 'php', $signals);
    }
}
