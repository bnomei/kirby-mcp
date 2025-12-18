<?php

const KIRBY_HELPER_DUMP = false;
const KIRBY_HELPER_E = false;

require __DIR__ . '/../../vendor/autoload.php';

new Bnomei\KirbyMcp\Install\RuntimeCommandsInstaller()->install(
  projectRoot: __DIR__,
  force: false,
  // Important: avoid recursive Kirby CLI bootstrapping.
  // If commandsRootOverride is omitted, the installer uses `kirby roots` internally,
  // which requires this `index.php`, which would call the installer again, etc.
  commandsRootOverride: __DIR__ . '/site/commands',
);

echo new Kirby([
  'roots' => [
    'index' => __DIR__,
    'site' => __DIR__ . '/site',
    'plugins' => __DIR__ . '/site/plugins',
    'content' => __DIR__ . '/content',
    'media' => __DIR__ . '/media',
    'assets' => __DIR__ . '/assets',
  ],
])->render();
