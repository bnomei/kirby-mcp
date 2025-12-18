<?php

/**
 * Copyright (c) 2025 Bruno Meilick
 * All rights reserved.
 *
 * This file is part of Kirby Kart and is proprietary software.
 * Unauthorized copying, modification, or distribution is prohibited.
 *
 * Inertia Adapter is based on code by
 * Copyright (c) 2020 Jon Gacnik
 * https://github.com/monoeq/kirby-inertia
 * (MIT license)
 */

use Kirby\Cms\Response;
use Kirby\Content\Field;

$page ??= kirby()->site()->page();
$template ??= $page->intendedTemplate();
$request = kirby()->request();

$inertia = array_filter([
    'component' => ucfirst($template->name()),
    'props' => $props ?? [],
    'url' => $request->url()->toString(),
    'version' => kart()->option('kerbs.version'),
]);

// only return partial props when requested
$only = array_filter(explode(',', $request->header('X-Inertia-Partial-Data') ?? ''));
if ($request->header('X-Inertia-Partial-Component') !== $inertia['component']) {
    $only = ['page', 'i18n', 'user', 'site', 'kart', 'shop']; // all
}

// build the result
foreach ($only as $key) {
    switch ($key) {
        case 'page': $inertia['props']['page'] = $page->toKerbs();
            break;
        default: $inertia['props'][$key] = kart()->option('kerbs.'.$key);
            break;
    }
}

// resolve fields and closures
$inertia['props'] = array_filter(array_map(function ($value) {
    $value = $value instanceof Field ? $value->value() : $value;

    return $value instanceof Closure ? $value() : $value;
}, $inertia['props']));

// return json when in inertia mode
if ($request->method() === 'GET' && $request->header('X-Inertia')) {
    echo Response::json($inertia, headers: [
        'Vary' => 'Accept',
        'X-Inertia' => 'true',
    ]);
    exit();
}

// otherwise render the app
snippet('kerbs/layout', slots: true);
?>
    <!-- Kirby Kart Plugin, Kerbs Theme: a Svelte 5 frontend with Inertia.js Adapter for Kirby CMS -->
    <main class="container" id="<?= $appId ?? 'app' ?>" data-page='<?= htmlspecialchars(\Kirby\Data\Json::encode($inertia), ENT_QUOTES, 'UTF-8') ?>'></main>
    <script defer src="<?= kirby()->urls()->media() ?>/plugins/bnomei/kart/kerbs.iife.js"></script>

<?php endsnippet();
