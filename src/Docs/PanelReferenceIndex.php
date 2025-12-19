<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Docs;

final class PanelReferenceIndex
{
    /**
     * @var array<string, string>
     */
    public const FIELD_TYPES = [
        'blocks' => 'Blocks',
        'checkboxes' => 'Checkboxes',
        'color' => 'Color',
        'date' => 'Date',
        'email' => 'Email',
        'entries' => 'Entries',
        'files' => 'Files',
        'gap' => 'Gap',
        'headline' => 'Headline',
        'hidden' => 'Hidden',
        'info' => 'Info',
        'layout' => 'Layout',
        'line' => 'Line',
        'link' => 'Link',
        'list' => 'List',
        'multiselect' => 'Multiselect',
        'number' => 'Number',
        'object' => 'Object',
        'pages' => 'Pages',
        'radio' => 'Radio',
        'range' => 'Range',
        'select' => 'Select',
        'slug' => 'Slug',
        'stats' => 'Stats',
        'structure' => 'Structure',
        'tags' => 'Tags',
        'tel' => 'Tel',
        'text' => 'Text',
        'textarea' => 'Textarea',
        'time' => 'Time',
        'toggle' => 'Toggle',
        'toggles' => 'Toggles',
        'url' => 'Url',
        'users' => 'Users',
        'writer' => 'Writer',
    ];

    /**
     * @var array<string, string>
     */
    public const SECTION_TYPES = [
        'fields' => 'Fields section',
        'files' => 'Files section',
        'info' => 'Info section',
        'pages' => 'Pages section',
        'stats' => 'Stats section',
    ];
}
