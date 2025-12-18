<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Project\KirbyRoots;

it('parses `kirby roots` var_dump output', function (): void {
    $output = <<<'TEXT'
array(2) {
  ["commands.local"]=>
  string(12) "/a/b/c/commands"
  ["site"]=>
  string(5) "/a/b/c/site"
}
TEXT;

    $roots = KirbyRoots::fromCliOutput($output);

    expect($roots->get('site'))->toBe('/a/b/c/site');
    expect($roots->commandsRoot())->toBe('/a/b/c/commands');
});
