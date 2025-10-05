# Copilot instructions for this repository

Short summary
- This is a Laravel 12 application (PHP ^8.2) scaffolded from the Laravel starter. The app exposes an API secured with Sanctum and a small Vite/Tailwind frontend. Key entry points: `routes/api.php`, `routes/web.php`, `resources/views/welcome.blade.php`.

What to read first (quick tour)
- `composer.json` — project scripts and required PHP packages (see `dev` and `test` scripts).
- `package.json` and `vite.config.js` — frontend build (Vite + Tailwind). Assets loaded using `@vite` in views.
- `routes/api.php` — API routes; note `AuthController` lives under `App\Http\Controllers\API` and the protected routes use `auth:sanctum` middleware.
- `config/sanctum.php` — Sanctum stateful domains and auth behavior.
- `phpunit.xml` — tests run against in-memory SQLite and set fast test env overrides (BCRYPT_ROUNDS=4, queue=sync, etc.).

Important workflows & commands (PowerShell examples)
- Install dependencies and set up environment:
```powershell
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate
```
- Start development (the repo has a Composer `dev` script that runs server, queue, pail and Vite concurrently):
```powershell
composer run dev
# or run frontend only
npm install
npm run dev
```
- Run tests:
```powershell
composer test
# or
php artisan test
```

Project-specific conventions and patterns
- API auth: routes under `routes/api.php` use Sanctum. Protect API endpoints using the `auth:sanctum` middleware group. Example pattern already present:
  - Public: `POST /api/register`, `POST /api/login` (see `AuthController`).
  - Protected: add resource routes inside the middleware group in `routes/api.php`.
- Controllers: put API controllers under `app/Http/Controllers/API` (see `AuthController` reference in `routes/api.php`). Reusable base controllers live in `app/Http/Controllers/Controller.php`.
- Models: application models are under `app/Models` (e.g., `User.php`). Follow the existing Eloquent conventions.
- Database: local development defaults to SQLite (`database/database.sqlite`); migrations live in `database/migrations`. Composer's `post-create-project-cmd` creates the sqlite file and runs migrations.

Testing & CI notes (discoverable behaviors)
- Tests expect an in-memory SQLite DB and faster hashing rounds (see `phpunit.xml`). When writing or running tests, rely on these overrides rather than mutating global config.
- Queue behavior in tests is `sync` by default (no background worker required). For integration checks use `QUEUE_CONNECTION=database` locally and run `php artisan queue:listen` or use `composer run dev`.

Integration points & external dependencies
- Sanctum for token/cookie auth (`laravel/sanctum`). Check `config/sanctum.php` and `SANCTUM_STATEFUL_DOMAINS` in `.env`.
- Vite + Tailwind + axios for frontend assets (`vite.config.js`, `resources/js`, `resources/css`).
- Mail defaults to `log` in `.env.example` (no external SMTP by default).

Where agents should make changes first
- Small features/API: add routes to `routes/api.php`, create controllers under `app/Http/Controllers/API`, and add migrations in `database/migrations`.
- Frontend assets: update `resources/js` and `resources/css`; adjust `vite.config.js` and `package.json` scripts as needed.

Files to reference while coding
- `composer.json`, `package.json`, `vite.config.js`
- `routes/api.php`, `routes/web.php`
- `app/Http/Controllers/`, `app/Models/`
- `database/migrations/`, `phpunit.xml`, `config/sanctum.php`, `.env.example`

If anything in this doc is unclear or you need more examples (e.g., a sample API controller, a sample migration, or how tests should be structured), tell me which area to expand and I will iterate.
