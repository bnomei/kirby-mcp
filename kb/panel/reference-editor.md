# Panel editing, preview and form actions

## Live editor

Open the page's preview view to edit its form beside the rendered changed version. As you type, the Panel stores changes on the server and reloads the preview; this is not the same as publishing them. Choose Live editor, Compare versions, Latest version or Changed version. Compare mode can synchronize scrolling.

Navigation within the preview follows the linked page in the form. External links and Panel links open a new tab. Pin the preview to navigate it independently of the form. The open button moves the preview to a second tab/window; closing it returns the preview to the main window.

Configure viewport choices in `site/config/config.php`:

```php
return [
    'panel' => [
        'preview' => [
            'sizes' => [
                'phone' => ['icon' => 'mobile', 'width' => '375px'],
                'desktop' => ['icon' => 'display', 'width' => '1280px'],
            ],
        ],
    ],
];
```

Defaults are 390px, 820px and 1440px. Sizes larger than the available browser space are disabled. Verify preview access, navigation, pin/unpin, save/discard and changed/latest separation on the actual site, especially with custom routes, caching or embedded scripts. Do not expose private preview tokens in logs or public links.

## Form feedback

- Highlighted labels identify unsaved fields. Disabled fields remain non-editable but allow selection/copying; disabling is not a secrecy or authorization mechanism.
- Date/time input follows the field's `display` format, recognizes English and Panel-language month names and retains invalid input for correction. Use bracketed literal text, e.g. `display: D. MMMM [in the year] YYYY`; storage format is separate.
- Option fields retain invalid choices and show validation errors. Decide on an explicit replacement; a save is not a cleanup operation.
- File picker dialogs accept drag-and-drop uploads when uploads are allowed and select the new files. Page picker dialogs show badges for selected children.
- A broken field can display its own error without taking down the view. Request errors expose request details; debug mode can additionally expose stack traces and configured code-editor links. Enable debug only on a safe development copy.

## Buttons in a blueprint

Use a non-value `buttons` field for links, dialogs or drawers:

```yaml
fields:
  actions:
    type: buttons
    label: Actions
    buttons:
      - text: Rename page
        icon: title
        dialog: '{{ page.panel.path }}/changeTitle'
      - text: Support
        icon: chat
        link: https://support.example.com
        target: _blank
```

Each button selects one of `link`, `dialog` or `drawer`. Text/title support translations; query templates can resolve model data. Optional `theme`, `badge` and `disabled` control presentation. A `buttons` query such as `page.shopButtons` must return a list of definitions, not custom-keyed entries. Enforce authorization and validation in the destination route, regardless of button visibility.

Do not write content for this field. Direct backend-request buttons are announced but not implemented in the verified beta.1 baseline; use a dialog/submit flow for actions.

## Existing Kirby 5 projects

The preview workflow expands the former preview view; custom view integrations use `mode`, not `versionId`. Existing built-in sections still convert automatically. See `kirby://kb/scenarios/83-migrate-kirby-5-to-6` for the migration sequence and `kirby://kb/panel/reference-runtime` for component contracts.

## Sources

- https://getkirby.com/releases/6/live-editor
- https://getkirby.com/releases/6/buttons-field
- https://getkirby.com/releases/6/more-details
- https://getkirby.com/releases/6/error-handling
