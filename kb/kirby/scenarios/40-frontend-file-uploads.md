# Scenario: Frontend file uploads (template + controller + file blueprint)

## Goal
Allow users to upload files from the frontend safely and attach metadata via a file blueprint.

## Inputs to ask for
- Upload destination page (fixed page like `storage` vs current page)
- Allowed file types and maximum size
- Maximum number of uploads per submit
- Whether uploads should be public, moderated, or quarantined

## Internal tools/resources to use
- Confirm roots: `kirby://roots` (or `kirby_roots`)
- Inspect file blueprints: `kirby_blueprints_index` + `kirby://blueprint/{encodedId}`
- Validate upload page rendering: `kirby_render_page`

## Implementation steps
1. Create a file blueprint used for uploaded files:
   - `site/blueprints/files/upload.yml`
2. Create a template with:
   - `<form enctype="multipart/form-data">`
   - a honeypot field
3. Create a controller that:
   - validates file count/type/size
   - stores uploads with `$page->createFile([...])` (after authentication/impersonation if required)
   - returns success/errors to the template

## Examples (cookbook pattern; abridged)

### Store uploads on a dedicated “storage” page
```php
$uploads = $kirby->request()->files()->get('file');
$kirby->impersonate('kirby');

foreach ($uploads as $upload) {
  page('storage')->createFile([
    'source'   => $upload['tmp_name'],
    'filename' => crc32($upload['name'] . microtime()) . '_' . $upload['name'],
    'template' => 'upload',
  ]);
}
```

## Verification
- Upload allowed files and confirm they are created under the destination page (e.g. `storage`).
- Confirm the file blueprint/template is applied and validation rules are enforced.

## Glossary quick refs

- kirby://glossary/file
- kirby://glossary/blueprint
- kirby://glossary/template
- kirby://glossary/roots

## Links
- Cookbook: File uploads: https://getkirby.com/docs/cookbook/forms/file-uploads
- Guide: Files: https://getkirby.com/docs/guide/files
- Reference: File blueprints: https://getkirby.com/docs/reference/panel/blueprints/file
- Reference: `$page->createFile()`: https://getkirby.com/docs/reference/objects/page/create-file
