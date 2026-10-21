# Flux

Flux is a modular analytics and event tracking application for websites, built with **Laravel 13**, **Vite**, **vanilla JavaScript**, and **semantic CSS** (BEM).

It collects, analyzes, and visualizes website statistics with a lightweight tracker and a clean admin interface.

## Core Features

- **Visit and event tracking:** records navigation data and custom events through a client-side tracker (`tracker.js`).
- **Dashboard:** responsive interface for exploring traffic, sources, devices, pages, and events.
- **Modular architecture:** extend functionality through plugins without modifying the core application.

## Plugin System

Flux is designed to be extended. Plugins can:

- Add dashboard tabs.
- Create complete pages with their own navigation.
- Manage their own database tables.
- Inject additional tracker scripts.
- Enrich visit data or react to tracking events through filters and hooks.

**Plugin development guide:** [docs/plugin-development.md](docs/plugin-development.md)

**Example plugin:** [AiAnalytics](https://github.com/mbarek-hani/AiAnalytics)

## Documentation

- [Frontend architecture](docs/frontend-architecture.md)
- [UI, design, and styles](docs/guide_ui_design_styles.md)
- [Plugin development](docs/plugin-development.md)

## Technology Stack

- **Backend:** Laravel 13, PHP 8.3
- **Frontend:** Blade, Vite, vanilla ES modules, semantic CSS with BEM
- **Charts and maps:** Chart.js, jsVectorMap
- **Icons:** Heroicons

## License

This project is licensed under the [MIT License](https://opensource.org/licenses/MIT).
