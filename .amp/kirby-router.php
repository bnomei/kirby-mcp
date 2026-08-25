<?php

declare(strict_types=1);

$documentRoot = realpath(__DIR__ . '/../tests/cms');
if ($documentRoot === false) {
    http_response_code(503);
    echo 'Kirby test fixture is missing. Run composer cms:starterkit.';

    return;
}

$publicUrl = getenv('PUBLIC_URL');
$publicUrl = is_string($publicUrl) ? parse_url($publicUrl) : false;
if (is_array($publicUrl) && isset($publicUrl['host'], $publicUrl['scheme'])) {
    $_SERVER['SERVER_NAME'] = $publicUrl['host'];
    $_SERVER['SERVER_PORT'] = $publicUrl['port'] ?? ($publicUrl['scheme'] === 'https' ? 443 : 80);
    $_SERVER['HTTPS'] = $publicUrl['scheme'] === 'https' ? 'on' : 'off';
}

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$requestPath = is_string($requestPath) ? rawurldecode($requestPath) : '/';
$assetPath = realpath($documentRoot . $requestPath);
$isPublicAsset = $requestPath === '/favicon.ico'
    || str_starts_with($requestPath, '/assets/')
    || str_starts_with($requestPath, '/media/');

if (
    $isPublicAsset
    && $assetPath !== false
    && str_starts_with($assetPath, $documentRoot . DIRECTORY_SEPARATOR)
    && is_file($assetPath)
    && strtolower(pathinfo($assetPath, PATHINFO_EXTENSION)) !== 'php'
) {
    return false;
}

$_SERVER['SCRIPT_NAME'] = '/index.php';

require $documentRoot . '/index.php';
