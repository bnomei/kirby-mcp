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
        'buttons' => 'Buttons',
        'checkboxes' => 'Checkboxes',
        'color' => 'Color',
        'date' => 'Date',
        'email' => 'Email',
        'entries' => 'Entries',
        'filelist' => 'File list',
        'filepicker' => 'File picker',
        'files' => 'Files (deprecated alias for filepicker)',
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
        'pagelist' => 'Page list',
        'pagepicker' => 'Page picker',
        'pages' => 'Pages (deprecated alias for pagepicker)',
        'password' => 'Password',
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
        'userpicker' => 'User picker',
        'users' => 'Users (deprecated alias for userpicker)',
        'writer' => 'Writer',
    ];

    /**
     * @var array<string, string>
     */
    public const SECTION_TYPES = [
        'fields' => 'Fields section (supported; automatically unwrapped into fields)',
        'files' => 'Files section (supported; automatically converted to filelist)',
        'info' => 'Info section (supported; automatically converted to info field)',
        'pages' => 'Pages section (supported; automatically converted to pagelist)',
        'stats' => 'Stats section (supported; automatically converted to stats field)',
    ];
}
