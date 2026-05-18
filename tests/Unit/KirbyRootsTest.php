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

it('parses `kirby roots` climate dump output', function (): void {
    $output = <<<'TEXT'
/app/vendor/league/climate/src/TerminalObject/Basic/Dump.php:28:
array(2) {
  'index' =>
  string(6) "/a/b/c"
  'commands.local' =>
  string(15) "/a/b/c/commands"
}
TEXT;

    $roots = KirbyRoots::fromCliOutput($output);

    expect($roots->get('index'))->toBe('/a/b/c');
    expect($roots->commandsRoot())->toBe('/a/b/c/commands');
});
