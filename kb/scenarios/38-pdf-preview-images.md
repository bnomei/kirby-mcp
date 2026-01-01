# Scenario: Generate PDF preview images (plugin + ImageMagick)

## Goal

Generate a preview image for uploaded PDFs, e.g.:

- convert the first page of a PDF into a PNG/JPG thumbnail
- store/generate it via hook or file method

## Inputs to ask for

- Where PDFs are uploaded (which page types/sections)
- Whether ImageMagick is available on the server (required by the cookbook approach)
- Which output format/size/quality is desired
- Whether previews are generated eagerly (on upload) or lazily (on demand)

## Internal tools/resources to use

- Inventory plugins: `kirby_plugins_index`
- Confirm roots: `kirby://roots` (for plugin placement and media paths)
- Validate output: `kirby_render_page` (to confirm preview URLs appear)

## Implementation steps

1. Create a plugin that adds:
   - a PDF preview generator class (wrapping ImageMagick)
   - either a hook (`file.create:after`) or a file method to generate previews
2. Ensure previews are stored in a safe location (e.g. under `media/`).
3. In templates/snippets, render the preview image if present.

## Examples (conceptual)

```php
Kirby::plugin('acme/pdfpreview', [
  'fileMethods' => [
    'pdfPreview' => function () {
      // generate/return preview url
    }
  ]
]);
```

## Verification

- Upload a PDF in the Panel.
- Confirm a preview image is generated and linked/rendered as expected.

## Glossary quick refs

- kirby://glossary/plugin
- kirby://glossary/hook
- kirby://glossary/media
- kirby://glossary/roots

## Links

- Cookbook: Create PDF preview images: https://getkirby.com/docs/cookbook/extensions/create-pdf-preview-images
- Guide: Plugins: https://getkirby.com/docs/guide/plugins
