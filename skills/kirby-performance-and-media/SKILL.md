---
name: kirby-performance-and-media
description: Improve Kirby performance and media delivery (cache tuning, CDN, responsive images, lazy loading). Use when optimizing page speed, caching, or image handling.
---

# Kirby Performance and Media

## Quick start

- Follow the workflow below, and run a full audit checklist when performance work spans multiple templates or plugins.

## Workflow

1. Call `kirby_init` and read `kirby://config/cache` and `kirby://config/thumbs`.
2. Inspect templates/snippets that render large collections of images:
   - `kirby_templates_index`
   - `kirby_snippets_index`
3. Search the KB with `kirby_search` (examples: "cdn asset and media urls", "lazy loading images", "responsive images srcset", "fine tune page cache", "conditional loading frontend libraries", "tailwindcss build workflow", "purgecss build workflow").
4. Apply minimal changes first:
   - use `srcset()`/`sizes` for responsive images
   - add `loading="lazy"` and `decoding="async"`
   - adjust cache ignore rules via blueprint options or fields
   - route assets/media through a CDN when required
5. Verify output and caching behavior:
   - `kirby_render_page(noCache=true)` for HTML
   - inspect generated media variants and cache headers in a browser
