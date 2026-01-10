# Panel pattern - area buttons with dialog actions

## Goal

Add custom buttons to a Panel area (for example the site area) that open dialogs and execute server-side actions when submitted.

## Inputs to ask for

- Target area to extend (site, pages, custom area)
- Button label, icon, theme, and placement
- Dialog fields, validation rules, and submit behavior
- Any external services or credentials needed by the action
- Which dialog component fits the UI (`k-form-dialog`, `k-text-dialog`, `k-remove-dialog`, `k-error-dialog`)

## MCP tools/resources to use

- kirby://roots
- kirby_plugins_index
- kirby://extension/panel-areas
- kirby://extension/panel-dialogs
- kirby://config/panel.dev

## Files to touch

- site/plugins/<plugin>/index.php
- site/plugins/<plugin>/index.js or site/plugins/<plugin>/src/index.js
- site/plugins/<plugin>/src/components/<ButtonOrDialog>.vue (optional)
- site/config/config.php (options for services)

## Implementation steps

1. Register an area extension and add a custom button definition with icon, text, theme, and dialog target.
2. Define a dialog with a load action that returns the dialog component and field schema.
3. Add a submit action that validates input and runs the server-side operation.
   - `load` creates a GET route at `/panel/dialogs/{pattern}`; `submit` creates a POST route.
   - Return `true` to close, or an `event` payload array; throw exceptions to show error dialogs.
4. If the button needs a custom dropdown UI, register a small Panel component that wraps a core button and dropdown.
5. Prefer kirbyup and kirbyuse for new work; if shipping prebuilt JS, keep the component minimal and focused on the button UX.

## Examples

- Buttons that open a dialog for a form-like action (post, share, or publish).
- A dropdown button that renders a list of external profile links.
- A dialog that posts to multiple services based on checkbox selection.

## Panel JS (K5)

```js
// site/plugins/example-area-tools/src/index.js
import { computed, ref, usePanel } from 'kirbyuse';

const ActionMenuButton = {
  name: 'KActionMenuButton',
  props: {
    text: String,
    icon: String,
    theme: String,
    dialog: String,
    items: Array,
  },
  setup(props) {
    const panel = usePanel();
    const button = ref(null);
    const menu = ref(null);
    const openDialog = () => {
      if (props.dialog) panel.dialog.open(props.dialog);
    };
    const toggleMenu = () => {
      if (menu.value) menu.value.toggle();
    };
    const go = (item) => {
      if (item.disabled) return;
      if (item.link) window.open(item.link, item.target || '_blank');
    };
    const anchor = computed(() => {
      const btn = button.value;
      return btn && (btn.$el || btn);
    });
    const options = computed(() =>
      (props.items || []).map((item) => ({
        text: item.text,
        icon: item.icon,
        disabled: !!item.disabled,
        click: () => go(item),
        target: item.target || null,
      })),
    );

    return { button, menu, anchor, options, openDialog, toggleMenu, go };
  },
  template: `
    <div class="k-action-menu-button">
      <k-button
        ref="button"
        :icon="icon"
        :text="text"
        :theme="theme"
        @click="openDialog"
      />
      <k-dropdown-content
        v-if="options.length"
        ref="menu"
        :anchor="anchor"
        :options="options"
      />
    </div>
  `,
};

panel.plugin('example/area-tools', {
  components: {
    'k-action-menu-button': ActionMenuButton,
  },
});
```

## Verification

- Panel button appears in the intended area and opens the dialog.
- Dialog submits successfully and shows a Panel notification on success or error.
- Actions are blocked when required config is missing.

## Links

- https://getkirby.com/docs/reference/plugins/extensions/panel-areas
- https://getkirby.com/docs/reference/plugins/extensions/panel-dialogs
- https://getkirby.com/docs/reference/plugins/ui

## Version notes (K5/K6)

- K5: Vue 2 components; avoid Vue 3-only syntax.
- K6: Vue 3 components; recheck any custom button wrappers and dialog components.
- K5 -> K6: if the plugin shipped prebuilt JS, rebuild with the correct Vue runtime.
