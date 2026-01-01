<?php

use Kirby\Cms\App;

App::plugin('mcp/test-routes', [
  'routes' => [
    [
      'pattern' => 'mcp-test/plugin-route',
      'method' => 'GET',
      'action' => static function () {
          return 'ok';
      },
    ],
  ],
]);
