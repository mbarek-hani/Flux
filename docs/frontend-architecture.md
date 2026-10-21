# Frontend Architecture

Flux uses server-rendered Blade views with a small, framework-free JavaScript layer. There is no Alpine.js, React, Vue, or jQuery.

The goals are:

- Keep behavior in focused ES modules.
- Keep Blade templates free of inline styles and inline event handlers.
- Make components idempotent so they can be initialized again after dynamically injected HTML.
- Expose a stable API for plugins through `window.FluxAdmin`.

---

## 1. Directory Layout

```
resources/js/
├── app.js                  application entry point
├── core/
│   ├── dom.js              query and DOM helpers
│   ├── http.js             CSRF-aware JSON requests
│   ├── ready.js            application readiness lifecycle
│   └── theme.js            theme storage and application
├── components/
│   ├── alert.js
│   ├── confirm.js
│   ├── copy.js
│   ├── dropdown.js
│   ├── flash.js
│   ├── modal.js
│   ├── sidebar.js
│   ├── tabs.js
│   └── theme-switcher.js
└── pages/
    ├── dashboard.js
    ├── marketplace.js
    └── settings.js
```

`app.js` imports Chart.js and jsVectorMap, exposes them as globals for the existing dashboard modules, initializes all components, and exposes the public API.

---

## 2. Boot Lifecycle

The boot sequence is:

1. `initComponents()` runs.
2. Shared component modules attach behavior to matching data attributes.
3. Page modules initialize their matching page.
4. `markReady()` dispatches `flux:ready` on `document`.
5. Plugin scripts can safely use `window.FluxAdmin`.

Use `FluxAdmin.onReady()` or the `flux:ready` event:

```js
window.FluxAdmin.onReady(() => {
    // safe to use the core API
});
```

For scripts that may load after readiness:

```js
const init = () => {
    // initialize
};

if (window.FluxAdmin?.isReady()) {
    init();
} else {
    document.addEventListener('flux:ready', init, { once: true });
}
```

---

## 3. Public API

`window.FluxAdmin` exposes:

```js
window.FluxAdmin.init(root);          // initialize components in a DOM subtree
window.FluxAdmin.isReady();           // whether boot has completed
window.FluxAdmin.onReady(callback);   // run now or after boot
window.FluxAdmin.modal.open(name);    // open a named modal
window.FluxAdmin.modal.close(name);   // close a named modal
window.FluxAdmin.theme.get();         // "light" or "dark"
window.FluxAdmin.theme.set(theme);    // apply and persist a theme
```

When inserting HTML dynamically, call `window.FluxAdmin.init(container)` after insertion. Component modules use `data-*-ready` flags to avoid duplicate listeners.

---

## 4. Data Attribute Conventions

Behavior is attached through data attributes. Do not use inline handlers such as `onclick`, Alpine directives, or `<script>` blocks inside views.

| Behavior | Markup |
|---|---|
| Modal opener | `data-modal-open="name"` |
| Modal closer | `data-modal-close="name"` |
| Modal root | `<x-modal name="name">` |
| Dropdown | `data-dropdown` plus trigger/menu attributes |
| Tabs | `data-tabs`, `data-tab-target`, `data-tab-panel` |
| Copy button | `data-copy-button`, `data-copy-target="element-id"` |
| Confirm submit | `data-confirm="Message"` on a `<form>` |
| Flash timeout | `data-flash`, `data-flash-timeout="3000"` |
| Theme switcher | `data-theme-switcher`, `data-theme-value="dark"` |
| Sidebar toggle | `data-sidebar-open`, `data-sidebar-close` |

Prefixed page-specific attributes are also used, such as `data-dashboard-*`, `data-marketplace-*`, and `data-plugin-settings`.

---

## 5. Events

- `flux:ready` is dispatched on `document` after boot.
- `flux:theme-changed` is dispatched on `document` with `{ theme }`.
- `flux:tab-changed` is dispatched on `document` with `{ group, target }`.
- `modal-opened` and `modal-closed` are dispatched on the modal element with the modal name as `detail`.

The existing window events `open-modal` and `close-modal` remain supported:

```js
window.dispatchEvent(new CustomEvent('open-modal', { detail: 'progress-modal' }));
window.dispatchEvent(new CustomEvent('close-modal', { detail: 'progress-modal' }));
```

---

## 6. HTTP Requests

Use `requestJson()` from `resources/js/core/http.js`:

```js
import { requestJson } from '../core/http';

const data = await requestJson(url, {
    method: 'POST',
    body: { enabled: true },
});
```

It adds the CSRF token, sends JSON by default, accepts `FormData`, parses JSON responses, and throws an `Error` containing the API error message on failure.

---

## 7. Adding a Page Module

1. Create `resources/js/pages/my-page.js`.
2. Export an `initMyPage(root = document)` function.
3. Query a page root such as `[data-my-page]`.
4. Guard against duplicate initialization with a `data-*-ready` flag.
5. Import and call the initializer from `resources/js/app.js`.

```js
import { select } from '../core/dom';

export function initMyPage(root = document) {
    const page = select('[data-my-page]', root);

    if (!page || page.dataset.myPageReady === 'true') {
        return;
    }

    page.dataset.myPageReady = 'true';

    // attach behavior
}
```

---

## 8. Plugin Frontend Integration

Plugins can publish stylesheets and scripts through their manifest or plugin class. Flux injects them into the admin layout only when the plugin is enabled.

- Publish files from `resources/assets/`.
- Return paths from `stylesheets()` and `scripts()`.
- Loaded scripts are deferred classic scripts, not ES modules.
- Use `window.FluxAdmin` and the `flux:ready` event for shared behavior.
- Use server-side hooks and filters for Blade output.

Available layout and page hooks include:

- `dashboard.widgets`, `dashboard.toolbar`
- `tab.{id}`
- `layout.sidebar.after`, `layout.topbar.actions`
- `site.show.after`, `plugin.show.after`
- `settings.sections`

See [Plugin Development](plugin-development.md) for the server-side plugin API and [UI, Design, and Styles](guide_ui_design_styles.md) for CSS conventions.

---

## 9. CSS Ownership

CSS layers and naming conventions are documented in the [UI, Design, and Styles Guide](guide_ui_design_styles.md). The main rules are:

- Import styles only through `resources/css/app.css`.
- Use `tokens`, `base`, `layouts`, `components`, `pages`, and `utilities` layers.
- Use `p-`, `c-`, and `u-` prefixes according to scope.
- Never add inline `style` attributes in Blade views.
- Use CSS custom properties for colors, spacing, radii, and transitions.
