# Flux Plugin Development Guide

> **Stack**: Laravel 13 · PHP 8.3 · vanilla ES modules · Vanilla CSS (BEM)  
> **Goal**: extend Flux without modifying its core.

Plugins are self-contained packages stored in `plugins/`. Flux discovers them lazily from their `manifest.json`, then only instantiates the classes of enabled plugins.

---

## 1. Quick Start

Create a plugin skeleton:

```bash
php artisan make:plugin MyPlugin
```

This generates `plugins/MyPlugin/` with a manifest, a plugin class, routes, views, assets, and a migrations directory.

Useful commands:

```bash
php artisan plugin:list
php artisan plugin:validate
php artisan plugin:cache
php artisan plugin:clear
php artisan plugin:enable my-plugin
php artisan plugin:disable my-plugin
php artisan plugin:install my-plugin
php artisan plugin:uninstall my-plugin
```

Plugins can also be enabled and disabled from the **Plugins** page in the dashboard.

---

## 2. Directory Structure

```text
plugins/
└── MyPlugin/                     ← PascalCase folder
    ├── manifest.json             ← discovery metadata
    ├── src/
    │   └── MyPlugin.php          ← plugin class
    ├── routes/
    │   ├── web.php               ← optional web routes
    │   └── api.php               ← optional API routes
    ├── resources/
    │   ├── views/                ← Blade views
    │   └── assets/               ← published CSS/JS
    └── database/
        └── migrations/           ← plugin migrations
```

Inactive plugin classes are never instantiated. If the plugin ships Composer dependencies in `plugins/MyPlugin/vendor/autoload.php`, that autoloader is loaded when the plugin is instantiated.

---

## 3. Manifest

```json
{
    "slug": "my-plugin",
    "name": "My Plugin",
    "version": "1.0.0",
    "description": "Adds advanced analytics.",
    "author": "John Doe",
    "license": "MIT",
    "class": "Plugins\\MyPlugin\\MyPlugin",
    "hooks": [
        { "name": "tab.my-plugin", "type": "render", "priority": 10 }
    ],
    "tabs": [
        { "id": "my-plugin", "label": "My plugin", "icon": "chart-bar" }
    ],
    "navigation": [
        {
            "label": "My plugin",
            "icon": "puzzle",
            "children": [
                { "label": "Reports", "route": "plugins.my-plugin.index" }
            ]
        }
    ],
    "settings": [
        {
            "key": "api_key",
            "label": "API key",
            "type": "password",
            "required": true,
            "help": "Used to call the external service."
        }
    ],
    "routes": {
        "web": {
            "middleware": ["web", "auth"],
            "prefix": "plugins/my-plugin",
            "name": "plugins.my-plugin."
        },
        "api": {
            "middleware": ["api"],
            "prefix": "api/plugins/my-plugin",
            "name": "api.plugins.my-plugin."
        }
    }
}
```

**Required fields:** `slug`, `name`, `version`, `class`.

- `slug`: unique kebab-case identifier.
- `class`: fully qualified class name, inside the `Plugins\` namespace.
- `hooks`: visual render hooks and filters. Entries may be strings or objects.
- `routes`: optional route configuration. Web routes default to `web` + `auth` middleware.

The class namespace must start with `Plugins\`. For example, `Plugins\MyPlugin\MyPlugin` resolves to `plugins/MyPlugin/src/MyPlugin.php`.

---

## 4. Plugin Class

Extend `App\Plugins\AbstractPlugin`:

```php
<?php

declare(strict_types=1);

namespace Plugins\MyPlugin;

use App\Plugins\AbstractPlugin;

class MyPlugin extends AbstractPlugin
{
    public function slug(): string
    {
        return 'my-plugin';
    }

    public function name(): string
    {
        return 'My Plugin';
    }

    public function version(): string
    {
        return '1.0.0';
    }
}
```

`AbstractPlugin` provides:

- route, view namespace, and listener registration in `register()`;
- asset publishing in `enable()` and removal in `disable()`;
- plugin migrations in `install()` and rollback in `uninstall()`;
- hook, filter, tracking, UI, settings, and asset contracts with safe defaults;
- configuration access through `$this->config('key', $default)`;
- path resolution through `$this->path('routes/web.php')`.

Override lifecycle methods only when you need custom behavior, and call the parent method first:

```php
public function enable(): void
{
    parent::enable();

    // initialize plugin data
}

public function disable(): void
{
    parent::disable();

    // pause background work
}
```

---

## 5. UI Extensions

### Tabs

```php
public function tabs(): array
{
    return [
        ['id' => 'my-plugin', 'label' => 'My plugin', 'icon' => 'chart-bar'],
    ];
}
```

Render tab content from the `tab.{id}` hook:

```php
public function renderHook(string $hook, array $data = []): string
{
    if ($hook === 'tab.my-plugin') {
        return view('my-plugin::tab', $data)->render();
    }

    return '';
}
```

### Navigation

```php
public function navigation(): array
{
    return [
        [
            'label' => 'My plugin',
            'icon' => 'puzzle',
            'children' => [
                ['label' => 'Reports', 'route' => 'plugins.my-plugin.index'],
            ],
        ],
        [
            'label' => 'Overview',
            'icon' => 'chart-bar',
            'route' => 'plugins.my-plugin.index',
        ],
    ];
}
```

### Settings

```php
public function settings(): array
{
    return [
        [
            'key' => 'api_key',
            'label' => 'API key',
            'type' => 'password',
            'required' => true,
            'help' => 'Used to call the external service.',
        ],
    ];
}
```

Read a value with `$this->config('api_key', 'default')`.

### Visual Hooks

Declare a hook in the manifest and render it from the plugin:

```php
public function renderHook(string $hook, array $data = []): string
{
    return view('my-plugin::widgets.summary', $data)->render();
}
```

In Blade:

```blade
@hook('dashboard.widgets')
```

Core views expose these hooks:

| Hook | Location |
|---|---|
| `dashboard.widgets` | Dashboard overview |
| `dashboard.toolbar` | Dashboard toolbar |
| `tab.{id}` | Dashboard plugin tabs |
| `layout.sidebar.after` | Below sidebar navigation |
| `layout.topbar.actions` | Top bar actions |
| `site.show.after` | Site details page |
| `plugin.show.after` | Plugin details page |
| `settings.sections` | Global settings page |

### Data Filters

```php
public function filterData(string $name, mixed $value, array $context = []): mixed
{
    if ($name === 'dashboard.title') {
        return strtoupper((string) $value);
    }

    return $value;
}
```

In Blade:

```blade
@filter('dashboard.title', $title)
```

---

## 6. Tracking Filters

The old `enrichTracking`, `afterVisit`, and `afterEvent` methods are gone. Plugins now use explicit filters and Laravel events.

### Filter a Visit

```php
use Illuminate\Http\Request;

public function filterVisit(array $data, Request $request): ?array
{
    if ($request->ip() === '127.0.0.1') {
        return null; // cancel tracking
    }

    $data['title'] = 'Custom: '.($data['title'] ?? '');

    return $data;
}
```

### Filter an Event

```php
public function filterEvent(array $data, Request $request): ?array
{
    if (($data['name'] ?? '') === 'blocked_event') {
        return null; // cancel tracking
    }

    return $data;
}
```

Returning `null` from either filter cancels persistence for that request.

### React After Persistence

Post-tracking behavior uses Laravel events only:

- `App\Analytics\Events\VisitRecorded`: `$event->visit`, `$event->data`, `$event->metadata`.
- `App\Analytics\Events\EventRecorded`: `$event->event`, `$event->data`, `$event->metadata`.
- `App\Events\PluginEnabled` and `App\Events\PluginDisabled`: `$event->slug`.

Declare listeners from the plugin:

```php
use App\Analytics\Events\VisitRecorded;
use Plugins\MyPlugin\Listeners\SendDiscordNotification;

public function listeners(): array
{
    return [
        VisitRecorded::class => [
            SendDiscordNotification::class, // implement ShouldQueue for heavy work
        ],
    ];
}
```

### Inject Tracker JavaScript

```php
public function trackerScript(): ?string
{
    return <<<'JS'
    window.addEventListener('scroll', function () {
        // client-side tracking logic
    });
    JS;
}
```

The script is appended to `/tracker.js` after the core tracker.

---

## 7. Database and Visit Metadata

Plugin migrations in `plugins/MyPlugin/database/migrations/` run when the plugin is installed or enabled. Flux records them in the `plugin_migrations` table and rolls them back on uninstall.

Prefix custom tables with the plugin slug:

```text
my_plugin_logs
```

To attach small values to a visit without creating a table, use `App\Analytics\Models\VisitMetadata`:

```php
use App\Analytics\Models\VisitMetadata;

VisitMetadata::create([
    'visit_id' => $visit->id,
    'plugin' => $this->slug(),
    'key' => 'engagement_score',
    'value' => '85',
]);
```

---

## 8. Assets, Routes, and Views

- Routes are registered automatically from `routes/web.php` and `routes/api.php`.
- Web route names default to `plugins.{slug}.`.
- API route names default to `api.plugins.{slug}.`.
- Views are available under the plugin namespace: `my-plugin::index`.
- Files in `resources/assets/` are published to `public/plugin-assets/{slug}/`.
- Declare extra assets:

```php
public function stylesheets(): array
{
    return ['plugin-assets/my-plugin/style.css'];
}

public function scripts(): array
{
    return ['plugin-assets/my-plugin/plugin.js'];
}
```

---

## 9. Lifecycle and Safe Mode

Plugin status is stored in the `plugins` table:

- `discovered`: manifest found, class not loaded.
- `installed`: migrations completed.
- `enabled`: registered and active.
- `disabled`: explicitly disabled.
- `failed`: registration or activation failed; the plugin is disabled and the error is stored in `last_error`.

A broken plugin cannot crash the application. Registration failures are caught, logged, and persisted as `failed`. Use `php artisan plugin:list` to inspect status.

Install and enable:

```bash
php artisan plugin:install my-plugin
```

Uninstall and roll back its migrations:

```bash
php artisan plugin:uninstall my-plugin
```

---

## 10. UI Components

Flux uses **Vanilla CSS with BEM** and **vanilla ES modules**. There is no client-side framework. For visual consistency and dark mode support, read the [UI, Design & Styles Guide](guide_ui_design_styles.md). For JavaScript behavior and the `window.FluxAdmin` API, read the [Frontend Architecture Guide](frontend-architecture.md).

Common Blade components:

| Component | Usage |
|---|---|
| Card | `<x-card title="Statistics">...</x-card>` |
| Button | `<x-button variant="primary">Save</x-button>` |
| Icon | `<x-custom-icon name="chart-bar" class="c-icon--md" />` |
| Input | `<x-input name="email" label="Email" type="email" :required="true" />` |
| Alert | `<x-alert type="warning">Warning</x-alert>` |

Plugin scripts are loaded as deferred classic scripts. Use `window.FluxAdmin` and the `flux:ready` event:

```js
const init = () => {
    document.querySelectorAll('[data-my-plugin-widget]').forEach((element) => {
        // initialize
    });
};

if (window.FluxAdmin?.isReady()) {
    init();
} else {
    document.addEventListener('flux:ready', init, { once: true });
}
```

---

**Happy building with Flux!**
