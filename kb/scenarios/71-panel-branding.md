# Scenario: Panel branding (logo + custom CSS)

## Goal

Customize the Panel appearance (branding) for clients by:

- setting panel title/logo
- injecting custom CSS

## Inputs to ask for

- Branding assets (logo/icon, font)
- Whether branding should differ per environment (dev/staging/prod)
- Whether custom CSS can be maintained long-term (Panel UI can change)

## Internal tools/resources to use

- Inspect config: `kirby://config/panel`
- Validate output by opening the Panel (manual)

## Implementation steps

1. Add Panel branding options in `site/config/config.php`.
2. Add a CSS file and load it via Panel config options.
3. Keep CSS minimal and use Panel variables where possible.

## Examples

```php
return [
  'panel' => [
    'css' => 'assets/css/custom-panel.css',
  ],
];
```

## Verification

- Open the Panel and confirm custom styles load without layout regressions.
- Verify the Panel remains usable after Kirby upgrades (CSS may need adjustments).

## Glossary quick refs

- kirby://glossary/panel
- kirby://glossary/asset
- kirby://glossary/option

## Links

- Quicktip: Panel branding: https://getkirby.com/docs/quicktips/panel-branding
- Quicktip: Customizing the Panel: https://getkirby.com/docs/quicktips/customizing-panel
- Guide: Configuration: https://getkirby.com/docs/guide/configuration
