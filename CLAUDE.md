# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Common Commands

All build and development tasks are managed via Gulp (Node.js). Run these with `npx gulp <task>`:

```bash
# First-time setup
npm install
npx gulp init          # Install deps, build assets, copy .dist config files

# Testing
npx gulp test          # Run all tests (PHP + coding style)
npx gulp test:php      # Run PHPUnit only
npx gulp test:cs       # Check PHP coding style (core)
npx gulp fix:cs        # Auto-fix PHP coding style (core)
npx gulp test:module:cs --module-name=ModuleName
npx gulp fix:module:cs --module-name=ModuleName

# CSS compilation
npx gulp css           # Compile SASS to CSS
npx gulp css:watch     # Watch and auto-compile
npx gulp css:module --module-name=ModuleName

# Database (run after modifying entities)
npx gulp db            # Update schema installer files + generate Doctrine proxies
npx gulp db:create-migration  # Create a new migration

# Internationalization
npx gulp i18n:template        # Extract strings to .pot file
npx gulp i18n:compile         # Compile .po to .mo
npx gulp i18n:module:template --module-name=ModuleName
npx gulp i18n:module:compile  --module-name=ModuleName
```

**PHPUnit directly** (test config at `application/test/phpunit.xml`):
```bash
./vendor/bin/phpunit -c application/test/phpunit.xml
./vendor/bin/phpunit -c application/test/phpunit.xml --filter TestClassName
```

## Architecture Overview

Omeka S is a web-based collections management platform built on the **Laminas Framework** (PHP), with Doctrine ORM, a REST API, and RDF/JSON-LD data model.

### Entry Points & Bootstrap

- `index.php` → `bootstrap.php` (Composer autoloader) → `Omeka\Mvc\Application::init()`
- All application code lives under the `Omeka\` namespace in `application/src/`
- Configuration is split: framework wiring is in `application/config/`, runtime secrets go in `config/` (gitignored)

### Module System

The codebase is structured around a **module architecture**:
- `application/` — the core "Omeka" module; always active
- `modules/` — optional installable modules (activated/deactivated via the UI/database)
- `themes/` — site presentation layer

Each module mirrors the core structure: `Module.php`, `src/`, `config/module.config.php`, `view/`, `language/`. Modules hook into core behavior via **Laminas EventManager** events (e.g., `api.execute.post`, `view.layout`).

### Configuration Files

| File | Purpose |
|------|---------|
| `application/config/application.config.php` | Laminas bootstrap, module paths, caching |
| `application/config/module.config.php` | Services, routes, forms for the core module |
| `config/database.ini` | MySQL credentials (gitignored; copy from `.dist`) |
| `config/local.config.php` | Runtime overrides: thumbnails, file storage, logger |
| `.php-cs-fixer.dist.php` | PHP code style (Laminas Coding Standard / PSR-2) |

### Data Layer

- **ORM**: Doctrine 2. Entities are in `application/src/Entity/` with PHP attributes.
- **Migrations**: Custom migration system in `application/data/migrations/`. After changing entities, run `npx gulp db` to regenerate the schema installer and Doctrine proxies.
- **Proxies**: Auto-generated to `application/data/doctrine-proxies/`.

### API Layer

All data operations go through a REST API layer:
- `application/src/Api/Adapter/` — converts request params to Doctrine queries; handles create/read/update/delete
- `application/src/Api/Representation/` — wraps entities for JSON-LD API responses
- `application/src/Api/Manager.php` — dispatches API requests

Data is modeled as RDF: items have typed values using vocabularies (Dublin Core, Schema.org, etc.) stored in `application/data/vocabularies/`.

### MVC Layer

Standard Laminas MVC:
- **Controllers**: `application/src/Controller/` (Admin and Site controllers separated)
- **Views**: `application/view/` — `.phtml` templates using Laminas view helpers
- **Routes**: defined in `application/config/routes.config.php`
- **Forms**: `application/src/Form/` using Laminas Form component

### Background Jobs

Long-running tasks (imports, exports, media ingestion) run as background jobs:
- Job classes in `application/src/Job/`
- Dispatched via the job dispatcher service; can run synchronously or via a queue

### Frontend Assets

- SASS source compiled via Gulp to `application/asset/css/`
- Vendor JS copied from `node_modules` to `application/asset/vendor/` by `npx gulp deps:js`
- jQuery, Chosen.js, Mirador (IIIF viewer), OpenSeadragon, jsTree are the primary frontend dependencies

### Dependency Injection

Laminas ServiceManager is used throughout. Services, factories, and aliases are registered in each module's `module.config.php` under `service_manager`. The service container is available in controllers, view helpers, and adapters.
