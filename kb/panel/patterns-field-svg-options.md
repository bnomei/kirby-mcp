# SVG option field

## Contract

Extend a concrete PHP option class such as `MultiselectField` for multiple stored selections or `SelectField` for one. Supply options through typed props/getters; query/API sources belong inside `options`. Register a Vue 3 field component. Store stable option keys, not SVG markup.

Decide the trusted icon source, single/multiple selection, translated labels, include/exclude rules and maximum list size before implementing.

## Implementation

1. Discover icons from a controlled directory or sprite. Normalize and allowlist names; never derive arbitrary filesystem paths from a submitted key.
2. Return option values and labels using the chosen core field's option contract. Provide a separate keyed preview map if the core option normalizer does not preserve custom metadata. Cache previews by source revision and language where labels are translated.
3. Prefer controlled image/sprite URLs. If inline SVG is necessary, sanitize scripts, event handlers, external references and unsafe embedded content before marking it as `HtmlString`. Trust wrappers are not sanitizers.
4. Declare `value`, option/preview props and `emits: ['input']`. Render choices and selected keys, emit a new array for multi-select changes, and never mutate a prop. Keep missing stored keys visible with an invalid-state label so an editor can deliberately remove or replace them.
5. Use `v-safe-html` for trusted inline previews and ordinary interpolation for labels. Use `k-dropdown` with its current props (`align-x`, not `align`) if a dropdown is needed; do not depend on private methods of the multiselect component.
6. Provide keyboard selection/removal, meaningful text labels, disabled state and bounded search results. Build Vue single-file components with kirbyup 4 and leave Vue external.

## Verification

Test selecting/removing, duplicate selections, empty input, unknown stored keys, translated labels, keyboard interaction and large icon sets. Test hostile SVG fixtures and confirm rejected markup never reaches a trust wrapper. Save/reload and check that only stable keys are persisted.

## Existing Kirby 5 plugins

Port PHP props/computed arrays to classes/getters and rebuild for Vue 3. Replace `k-dropdown-content` with `k-dropdown`, and raw `v-html` with the trust-aware rendering above. Invalid options remain stored in Kirby 6; do not rely on saving to discard removed icons.

## References

- `kirby://kb/panel/reference-fields`
- https://getkirby.com/releases/6/migration/plugin-developers/trusted-html
- https://getkirby.com/releases/6/changelog#breaking-changes__panel__frontend
