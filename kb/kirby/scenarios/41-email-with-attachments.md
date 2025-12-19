# Scenario: Email with attachments (file upload + send)

## Goal

Accept file uploads in a form and send them as email attachments.

## Inputs to ask for

- Which files are accepted (CV PDFs, images, etc.)
- Maximum file size and count
- Whether files should be stored permanently or only used transiently
- Recipient routing (fixed recipient vs based on form selection)

## Internal tools/resources to use

- Confirm roots: `kirby://roots` (or `kirby_roots`)
- Inspect email config: `kirby://config/email`
- Validate controller behavior: `kirby_render_page`

## Implementation steps

1. Create a multipart form:
   - `enctype="multipart/form-data"`
2. In the controller:
   - validate form fields and uploads
   - move/store uploads (or keep temporary paths)
   - call `$kirby->email([... 'attachments' => [...]])`
3. Add email templates for text + HTML variants.
4. Clean up temporary files if you don’t store uploads.

## Examples (cookbook pattern; abridged)

```php
$kirby->email([
  'template'    => 'email',
  'from'        => 'yourcontactform@yourcompany.com',
  'replyTo'     => $data['email'],
  'to'          => 'you@yourcompany.com',
  'subject'     => esc($data['name']) . ' applied for job ' . esc($data['reference']),
  'data'        => [
    'message' => esc($data['message']),
    'name'    => esc($data['name']),
  ],
  'attachments' => $attachments,
]);
```

## Verification

- Submit with an allowed attachment and confirm it arrives.
- Submit with disallowed types/size and confirm validation errors.

## Glossary quick refs

- kirby://glossary/roots
- kirby://glossary/controller
- kirby://glossary/option
- kirby://glossary/template

## Links

- Cookbook: Email with attachments: https://getkirby.com/docs/cookbook/forms/email-with-attachments
- Guide: Emails: https://getkirby.com/docs/guide/emails
- Reference: Email options: https://getkirby.com/docs/reference/system/options/email
