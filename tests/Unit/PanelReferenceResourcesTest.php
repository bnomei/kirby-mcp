<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\Resources\PanelReferenceResources;
use Mcp\Exception\ResourceReadException;

it('lists Kirby Panel field resources', function (): void {
    $resource = new PanelReferenceResources();
    $markdown = $resource->fieldsList();

    expect($markdown)->toContain('kirby://field/blocks');
    expect($markdown)->toContain('kirby://field/email');
});

it('lists Kirby Panel section resources', function (): void {
    $resource = new PanelReferenceResources();
    $markdown = $resource->sectionsList();

    expect($markdown)->toContain('kirby://section/fields');
    expect($markdown)->toContain('kirby://section/files');
});

it('validates field types as slugs', function (): void {
    $resource = new PanelReferenceResources();

    expect(fn () => $resource->field('bad slug'))->toThrow(ResourceReadException::class);
    expect(fn () => $resource->field('../blocks'))->toThrow(ResourceReadException::class);
});

it('validates section types as slugs', function (): void {
    $resource = new PanelReferenceResources();

    expect(fn () => $resource->section('bad slug'))->toThrow(ResourceReadException::class);
    expect(fn () => $resource->section('../fields'))->toThrow(ResourceReadException::class);
});
