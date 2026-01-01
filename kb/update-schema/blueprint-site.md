# Blueprint: site (content update)

## Blueprint summary

Site blueprints define the editable fields for the singleton `$site` object. Updates map to `$site->update($data, $language, $validate)`.

## Storage format

Site content is stored in the root content file, e.g.:

```yaml
title: My Site
```

## Runtime value

`$site->content($language)->toArray()` returns a field-value map.

## Update payload (kirby_update_site_content)

```json
{ "title": "My Site" }
```

## Merge strategy

Replace field values by key. For merges/append, read existing values, merge in memory, then update.

## Edge cases

- The site has no UUID; use the singleton `$site` (or the `kirby://site/content` resource).
- Many site blueprints only define sections, not fields. Only defined fields can be validated.
- Complex fields (blocks/layout/structure) require their own update schemas.

## MCP: Inspect/verify

- Read content: `kirby_read_site_content` or `kirby://site/content`
- Read blueprint config: `kirby_blueprint_read` or `kirby://blueprint/site`
- Field storage guidance: `kirby://fields/update-schema`, `kirby://field/{type}/update-schema`

## Glossary quick refs

- kirby://glossary/site
- kirby://glossary/content
- kirby://glossary/field

## Links

- https://getkirby.com/docs/reference/panel/blueprints/site
