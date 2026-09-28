<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Install\RuntimeCommandsInstaller;
use Kirby\Cms\App;
use Kirby\Cms\User;

$root = dirname(__DIR__);
$cmsRoot = $root . '/tests/cms';
$secretsRoot = $root . '/.amp/secrets';

if (!is_file($cmsRoot . '/kirby/vendor/autoload.php')) {
    fwrite(STDERR, "Kirby fixture is missing; run composer cms:starterkit first.\n");
    exit(1);
}

if (!is_dir($secretsRoot) && !mkdir($secretsRoot, 0700, true) && !is_dir($secretsRoot)) {
    throw new RuntimeException('Unable to create the fixture secrets directory.');
}
chmod($secretsRoot, 0700);

$secret = static function (string $path, int $bytes) use ($secretsRoot): string {
    $file = $secretsRoot . '/' . $path;
    if (is_file($file)) {
        $value = trim((string) file_get_contents($file));
        if ($value !== '') {
            chmod($file, 0600);

            return $value;
        }
    }

    $value = bin2hex(random_bytes($bytes));
    $temporary = $file . '.tmp.' . bin2hex(random_bytes(4));
    file_put_contents($temporary, $value . PHP_EOL, LOCK_EX);
    chmod($temporary, 0600);
    rename($temporary, $file);

    return $value;
};

$password = $secret('kirby-panel-password', 24);
$secret('kirby-mcp-token', 32);
$email = 'demo@example.test';
file_put_contents($secretsRoot . '/kirby-panel-email', $email . PHP_EOL, LOCK_EX);
chmod($secretsRoot . '/kirby-panel-email', 0600);

// Load this library while retaining the downloaded fixture's Kirby as authoritative.
$fixtureLoader = require $cmsRoot . '/kirby/vendor/autoload.php';
require $root . '/vendor/autoload.php';
$fixtureLoader->unregister();
$fixtureLoader->register(true);

$installation = (new RuntimeCommandsInstaller())->install($cmsRoot, force: true);
if (!$installation->ok()) {
    throw new RuntimeException('Unable to install the fixture MCP adapter and commands.');
}

$app = new App(['roots' => ['index' => $cmsRoot]]);
/** @var User $user */
$user = $app->impersonate('kirby', static function () use ($app, $email, $password): User {
    $user = $app->user($email);
    if ($user === null) {
        return $app->users()->create([
            'email' => $email,
            'password' => $password,
            'role' => 'admin',
        ]);
    }

    return $user->isAdmin() ? $user : $user->changeRole('admin');
});

file_put_contents($secretsRoot . '/kirby-panel-user-id', $user->id() . PHP_EOL, LOCK_EX);
chmod($secretsRoot . '/kirby-panel-user-id', 0600);

$configFile = $cmsRoot . '/.kirby-mcp/mcp.json';
if (!is_dir(dirname($configFile))) {
    mkdir(dirname($configFile), 0775, true);
}
$config = [];
$existingConfigFile = is_file($configFile) ? $configFile : $cmsRoot . '/.kirby-mcp/config.json';
if (is_file($existingConfigFile)) {
    $decoded = json_decode((string) file_get_contents($existingConfigFile), true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($decoded)) {
        throw new RuntimeException('Fixture MCP config must contain a JSON object.');
    }
    $config = $decoded;
}
$activity = isset($config['activity']) && is_array($config['activity']) ? $config['activity'] : [];
$activity['enabled'] = true;
$config['activity'] = $activity;
file_put_contents(
    $configFile,
    json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL,
    LOCK_EX,
);

fwrite(STDOUT, "Kirby Panel fixture prepared.\n");
