# Scenario: Basic contact form (controller validation + email)

## Goal

Implement a simple contact form with:

- a form template
- a controller that validates and sends email
- spam protection via a honeypot field

## Inputs to ask for

- Target page/template name (e.g. `contact`)
- Required fields (name, email, message, optional subject)
- Email transport configuration (SMTP, sendmail, API) and sender/recipient addresses
- Whether to use additional spam protection (rate limit, captcha)

## Internal tools/resources to use

- Confirm roots: `kirby://roots` (or `kirby_roots`)
- Inspect email config: `kirby://config/email`
- Validate output and errors: `kirby_render_page`

## Implementation steps

1. Create the template with a POST form and a honeypot field.
2. Create the controller:
   - on POST: read inputs with `get()`
   - validate via `invalid($data, $rules, $messages)`
   - send email via `$kirby->email([...])`
3. Add email templates:
   - `site/templates/emails/email.php`
   - `site/templates/emails/email.html.php`
4. Escape all user-controlled output (`esc()`, `->escape()`) when rendering back into the form.

## Examples (cookbook pattern; abridged)

### Controller: validate and send

`site/controllers/contact.php`

```php
<?php

return function ($kirby, $pages, $page) {
  $alert = null;

  if ($kirby->request()->is('POST') && get('submit')) {
    if (empty(get('website')) === false) {
      go($page->url());
    }

    $data = [
      'name'  => get('name'),
      'email' => get('email'),
      'text'  => get('text'),
    ];

    $rules = [
      'name'  => ['required', 'minLength' => 3],
      'email' => ['required', 'email'],
      'text'  => ['required', 'minLength' => 3, 'maxLength' => 3000],
    ];

    if ($invalid = invalid($data, $rules)) {
      $alert = $invalid;
    } else {
      $kirby->email([
        'template' => 'email',
        'from'     => 'yourcontactform@yourcompany.com',
        'replyTo'  => $data['email'],
        'to'       => 'you@yourcompany.com',
        'subject'  => esc($data['name']) . ' sent you a message',
        'data'     => [
          'text'   => esc($data['text']),
          'sender' => esc($data['name']),
        ],
      ]);

      $success = 'Your message has been sent, thank you.';
      $data = [];
    }
  }

  return [
    'alert'   => $alert,
    'data'    => $data ?? false,
    'success' => $success ?? false,
  ];
};
```

## Verification

- Submit with invalid fields and confirm errors show.
- Submit a valid message and confirm email delivery (or captured emails in dev).

## Glossary quick refs

- kirby://glossary/template
- kirby://glossary/controller
- kirby://glossary/field
- kirby://glossary/roots

## Links

- Cookbook: Basic contact form: https://getkirby.com/docs/cookbook/forms/basic-contact-form
- Guide: Emails: https://getkirby.com/docs/guide/emails
- Reference: `invalid()` helper: https://getkirby.com/docs/reference/templates/helpers/invalid
- Guide: Escaping: https://getkirby.com/docs/guide/templates/escaping
