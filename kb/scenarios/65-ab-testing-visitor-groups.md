# Scenario: A/B testing with a simple visitor group function

## Goal

Serve different variants (A/B) based on a stable visitor group, e.g. to:

- test different call-to-action blocks
- test layout variants

## Inputs to ask for

- Which variants exist and where they apply (template branch vs snippets)
- Grouping strategy (IP-based, cookie/session-based, user id-based)
- Whether the experiment must be deterministic over time
- Whether IP-based grouping is acceptable behind proxies/CDNs

## Internal tools/resources to use

- Inventory plugins: `kirby_plugins_index`
- Validate rendering: `kirby_render_page`

## Implementation steps

1. Implement a visitor grouping function (often as a small plugin helper).
2. Use `$kirby->visitor()->ip()` + `ip2long()` to deterministically assign group A/B.
3. Branch rendering based on `visitorgroup('a')`/`visitorgroup('b')`.
4. Keep experiments isolated and removable.

## Examples (cookbook idea)

```php
function visitorgroup(string $which = null)
{
  $ip = kirby()->visitor()->ip();
  $group = (ip2long($ip) % 2) ? 'a' : 'b';

  return $which === null ? $group : $group === $which;
}
```

```php
<?php
/**
 * @var Kirby\Cms\App $kirby
 * @var Kirby\Cms\Site $site
 * @var Kirby\Cms\Page $page
 */
?>

<?php if (visitorgroup('a') === true): ?>
  <?php snippet('cta-a') ?>
<?php else: ?>
  <?php snippet('cta-b') ?>
<?php endif ?>
```

## Verification

- Refresh and confirm the same visitor stays in the same group (deterministic).
- Test variant rendering with multiple clients (or by overriding the group).

## Glossary quick refs

- kirby://glossary/plugin
- kirby://glossary/snippet

## Links

- Cookbook: A/B testing with Kirby: https://getkirby.com/docs/cookbook/unclassified/ab-testing
