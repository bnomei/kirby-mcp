<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Project\EnvironmentDetector;

it('defaults to plain php for the cms fixture', function (): void {
    $info = (new EnvironmentDetector())->detect(cmsPath());

    expect($info->localRunner)->toBe('php');
});

it('detects ddev via .ddev/config.yaml', function (): void {
    $root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kirby-mcp-env-ddev-' . bin2hex(random_bytes(4));
    $ddevDir = $root . DIRECTORY_SEPARATOR . '.ddev';
    mkdir($ddevDir, 0777, true);
    file_put_contents($ddevDir . DIRECTORY_SEPARATOR . 'config.yaml', 'name: test');

    try {
        $info = (new EnvironmentDetector())->detect($root);
        expect($info->localRunner)->toBe('ddev');
        expect($info->signals)->toHaveKey('.ddev/config.yaml', 'found');
    } finally {
        @unlink($ddevDir . DIRECTORY_SEPARATOR . 'config.yaml');
        @rmdir($ddevDir);
        @rmdir($root);
    }
});

it('detects docker via compose or Dockerfile', function (): void {
    $root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kirby-mcp-env-docker-' . bin2hex(random_bytes(4));
    mkdir($root, 0777, true);
    file_put_contents($root . DIRECTORY_SEPARATOR . 'docker-compose.yml', 'version: \"3\"');

    try {
        $info = (new EnvironmentDetector())->detect($root);
        expect($info->localRunner)->toBe('docker');
        expect($info->signals)->toHaveKey('docker-compose.yml', 'found');
    } finally {
        @unlink($root . DIRECTORY_SEPARATOR . 'docker-compose.yml');
        @rmdir($root);
    }
});

it('detects herd via .herd.yml', function (): void {
    $root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kirby-mcp-env-herd-' . bin2hex(random_bytes(4));
    mkdir($root, 0777, true);
    file_put_contents($root . DIRECTORY_SEPARATOR . '.herd.yml', 'services: []');

    try {
        $info = (new EnvironmentDetector())->detect($root);
        expect($info->localRunner)->toBe('herd');
        expect($info->signals)->toHaveKey('.herd.yml', 'found');
    } finally {
        @unlink($root . DIRECTORY_SEPARATOR . '.herd.yml');
        @rmdir($root);
    }
});
