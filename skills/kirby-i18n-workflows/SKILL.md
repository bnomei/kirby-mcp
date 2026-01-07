---
name: kirby-i18n-workflows
description: Manage Kirby multi-language workflows, translations, and localized labels. Use when dealing with languages, translation keys, placeholders, or importing/exporting translations.
---

# Kirby i18n Workflows

## Workflow

1. Confirm language setup with `kirby://config/languages` and locate language files via `kirby://roots`.
2. Inspect templates/snippets/controllers for translation usage; use `rg` to find `t(` calls if needed.
3. Search the KB with `kirby_search` (examples: "translate field options", "find translation keys", "import export translations", "filter by language", "language variables placeholders", "translate exception messages").
4. Update language files (`site/languages/*.php`) or config maps for option labels.
5. Ensure templates render translated labels (not stored keys) and use fallbacks.
6. Verify by rendering representative pages in each language (`kirby_render_page`).
