<?php

use Bnomei\KirbyMcp\Mcp\KirbyMcpRoutes;

/**
 * The config file is optional. It accepts a return array with config options
 * Note: Never include more than one return statement, all options go within this single return array
 * In this example, we set debugging to true, so that errors are displayed onscreen.
 * This setting must be set to false in production.
 * All config options: https://getkirby.com/docs/reference/system/options
 */
$mcpRoutes = [];

// The downloaded kit is not Composer-based; load the library before plugin discovery
// to model the supported host installation, keeping the kit's Kirby authoritative.
if (!class_exists(\Bnomei\KirbyMcp\Mcp\Plugin::class)) {
    $fixtureLoader = require dirname(__DIR__, 2) . '/kirby/vendor/autoload.php';
    require dirname(__DIR__, 4) . '/vendor/autoload.php';

    // Keep the fixture's bundled Kirby version authoritative after loading this package.
    $fixtureLoader->unregister();
    $fixtureLoader->register(true);
}

if (PHP_SAPI === 'cli-server' && getenv('KIRBY_MCP_FIXTURE_HTTP_ROUTE') === '1') {
    $mcpRoutes = KirbyMcpRoutes::routes(projectRoot: dirname(__DIR__, 2));
}

return [
  'debug' => true,
  'yaml.handler' => 'symfony', // already makes use of the more modern Symfony YAML parser: https://getkirby.com/docs/reference/system/options/yaml (will become the default in a future Kirby version)
  'vendorname.pluginname.someoption' => 5,
  'vendorname.pluginname.secretoption' => 'super-secret-value',
  'vendorname.pluginname.apiKey' => 'sk-live-1234567890',
  'vendorname.pluginname.arrayoption' => [
    'a' => 1,
    'b' => [
      'c' => 2,
    ],
  ],
  'vendorname.pluginname.closureoption' => static fn (): int => 123,
  'routes' => [
    ...$mcpRoutes,
    [
      'pattern' => 'mcp-test/config-route',
      'method' => 'GET',
      'action' => static function () {
          return 'ok';
      },
    ],
  ],
];
