<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\Resources\UuidResources;
use Kirby\Cms\App;

/**
 * @return array{0: Closure(): ?App, 1: Closure(?App): void}
 */
function uuidResourcesAppAccessors(): array
{
    $getter = static function (): ?App {
        return App::instance(lazy: true);
    };

    $setter = static function (?App $instance): void {
        if ($instance === null) {
            App::destroy();
            return;
        }

        App::instance($instance);
    };

    return [$getter, $setter];
}

it('generates a uuid string via the resource', function (): void {
    [$getInstance, $setInstance] = uuidResourcesAppAccessors();
    $previousInstance = $getInstance();
    $previousWhoops = App::$enableWhoops;
    App::$enableWhoops = false;

    $app = new App(['roots' => ['index' => cmsPath()]]);
    $setInstance($app);

    try {
        $resource = new UuidResources();
        $uuid = $resource->uuidNew();

        expect($uuid)->toBeString()->not()->toBe('');
    } finally {
        App::$enableWhoops = $previousWhoops;
        $setInstance($previousInstance);
    }
});
