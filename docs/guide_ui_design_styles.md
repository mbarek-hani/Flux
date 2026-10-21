# UI, Design, and Styles Guide

> **Stack:** vanilla CSS with BEM · CSS custom properties · vanilla ES modules
>
> **Audience:** core contributors and plugin authors.

Flux uses a small, explicit design system. There is no utility framework and no client-side framework. Styles live in layered CSS files, and interactivity lives in focused ES modules.

---

## 1. CSS Architecture

All styles are imported from `resources/css/app.css` in this layer order:

```css
@layer tokens, base, layouts, components, pages, utilities;
```

```
resources/css/
├── app.css
├── tokens/
│   ├── colors.css       color and semantic tokens
│   └── design.css       spacing, radii, typography, transitions
├── base/
│   └── reset.css
├── layouts/
│   ├── app.css
│   └── guest.css
├── components/
│   ├── alert.css
│   ├── button.css
│   ├── card.css
│   ├── input.css
│   ├── modal.css
│   └── ...
├── pages/
│   ├── common.css
│   ├── dashboard.css
│   ├── marketplace.css
│   └── auth.css
└── utilities/
    └── utilities.css
```

Use the prefix that matches the scope:

- `p-` for page and layout structure, such as `.p-page`, `.p-container`, `.p-grid`.
- `c-` for reusable components, such as `.c-btn`, `.c-input`, `.c-card`.
- `u-` for small utilities, such as `.u-flex`, `.u-mb-0`, `.u-text-center`.

**Never use inline `style="..."` attributes in Blade views.** Add a class in the appropriate CSS layer instead. Dynamic values set by JavaScript, such as chart colors or progress width, are allowed.

---

## 2. Design Tokens

Use tokens instead of hard-coded colors:

- Surfaces: `var(--surface-white)`, `var(--gray-50)`, `var(--gray-900)`.
- Borders: `var(--gray-200)`, `var(--gray-300)`.
- Text: `var(--gray-500)` for secondary text, `var(--gray-900)` for primary text.
- Success: `var(--success-bg)`, `var(--success-border)`, `var(--success-text)`.
- Error: `var(--error-bg)`, `var(--error-border)`, `var(--error-text)`.
- Warning: `var(--warning-bg)`, `var(--warning-border)`, `var(--warning-text)`.
- Information: `var(--info-bg)`, `var(--info-border)`, `var(--info-text)`.

Chart and map colors also use tokens, including `--chart-traffic-1`, `--chart-traffic-2`, `--chart-events`, `--chart-1` through `--chart-5`, `--chart-device-1` through `--chart-device-4`, and the `--map-*` tokens.

---

## 3. Dark Mode

Dark mode is applied by setting `data-theme="dark"` on the `<html>` element. The preference is stored under the existing `theme` localStorage key.

A small boot script in `resources/views/layouts/partials/head.blade.php` applies the stored theme before the page renders to prevent flashing. Do not replace it with a deferred module.

Use semantic tokens exclusively so plugin styles support both themes automatically:

```css
/* plugins/MyPlugin/resources/assets/style.css */
.c-my-plugin-box {
    background-color: var(--surface-white);
    border: 1px solid var(--gray-200);
    color: var(--gray-900);
}

.c-my-plugin-box:hover {
    background-color: var(--gray-50);
}
```

---

## 4. Core Blade Components

Prefer the core components over custom markup. They already handle accessibility, errors, and dark mode.

| Component | Usage |
|---|---|
| Button | `<x-button variant="primary">Save</x-button>` |
| Button link | `<x-button href="{{ route('sites.index') }}">Cancel</x-button>` |
| Input | `<x-input name="email" label="Email" type="email" :required="true" />` |
| Card | `<x-card title="Statistics">...</x-card>` |
| Stats card | `<x-stats-card title="Visits" :value="$count" icon="chart-bar" />` |
| Alert | `<x-alert type="warning" message="Check this value." />` |
| Modal | `<x-modal name="confirm-modal">...</x-modal>` |
| Dropdown | `<x-dropdown>...</x-dropdown>` |
| Icon | `<x-custom-icon name="chart-bar" class="c-icon--md" />` |

Button variants are `primary`, `danger`, and `default`. Button sizes are `sm`, `md`, and `lg`. Alert types are `success`, `error`, `warning`, and `info`.

Open and close modals with data attributes or the JavaScript API:

```blade
<x-button data-modal-open="confirm-modal">Open</x-button>

<x-modal name="confirm-modal">
    <x-button data-modal-close="confirm-modal">Close</x-button>
</x-modal>
```

```js
window.FluxAdmin.modal.open('confirm-modal');
window.FluxAdmin.modal.close('confirm-modal');
```

---

## 5. Plugin CSS

If the core components are not enough, publish plugin-specific CSS:

1. Create `resources/assets/style.css` inside your plugin.
2. Follow the BEM convention and use CSS tokens.
3. Return the published path from the plugin's `stylesheets()` method.

```php
public function stylesheets(): array
{
    return ['plugin-assets/my-plugin/style.css'];
}
```

Flux copies the file to `public/plugin-assets/{slug}/` and injects it into the admin `<head>`.

---

## 6. Frontend Interactivity

Flux does not use React, Vue, jQuery, or Alpine.js. Behavior is implemented with vanilla ES modules.

- Core modules live in `resources/js/core/`.
- Reusable behaviors live in `resources/js/components/`.
- Page-specific modules live in `resources/js/pages/`.
- Use data attributes, not inline event handlers such as `onclick` or `@click`.
- Expose reusable behavior through `window.FluxAdmin`.
- Listen for `flux:ready` when plugin code needs the core API.

```js
const init = () => {
    document.querySelectorAll('[data-my-widget]').forEach((element) => {
        // initialize widget
    });
};

if (window.FluxAdmin?.isReady()) {
    init();
} else {
    document.addEventListener('flux:ready', init, { once: true });
}
```

See [Frontend Architecture](frontend-architecture.md) for module conventions, events, and the `FluxAdmin` API.
