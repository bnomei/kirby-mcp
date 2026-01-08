<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\Resources\UuidResources;
use Kirby\Cms\App;

/**
 * @return array{0: Closure(): ?App, 1: Closure(?App): void}
 */
function uuidResourcesAppAccessors(): array
{
    $property = new ReflectionProperty(App::class, 'instance');
    $property->setAccessible(true);

    $getter = static function () use ($property): ?App {
        $value = $property->getValue();

        return $value instanceof App ? $value : null;
    };

    $setter = static function (?App $instance) use ($property): void {
        $property->setValue(null, $instance);
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
