# تقييم التدريب (Internship Ratings)

An Arabic, right-to-left public platform where students rate Saudi cooperative
and summer internship providers ("جهات" / companies). Reviewers submit detailed,
weighted ratings of a training experience; visitors browse and compare providers
before choosing where to train. The site is anonymous — there is no public
account system; only admins log in, and only to moderate content.

## Tech stack

- **PHP 8.4**, **Laravel 13**
- **Filament v5** — admin panel at `/admin` (owns admin auth; Fortify is removed)
- **Livewire v4 + Volt** — public pages as single-file components under `resources/views/pages/`
- **Flux UI** (`livewire/flux`) and **Mary UI** (`robsontenorio/mary`) component libraries
- **Tailwind CSS v4** + Vite
- **Cloudflare Turnstile** (`njoguamos/laravel-turnstile`) on the public rating form
- **Pest v4** for tests, **Pint** for formatting
- **SQLite** in development, **PostgreSQL** in production
- **Laravel Octane** (RoadRunner) supported; deployed via Coolify + nixpacks

## Local setup

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite   # SQLite is the default DB_CONNECTION
npm install
npm run build
php artisan migrate --seed
```

A `composer setup` script bundles most of the above (install, env copy,
key:generate, migrate, npm install, npm run build).

To run everything for local development (server, queue, logs, Vite) in one
command:

```bash
composer run dev
```

## Running tests

```bash
php artisan test --compact
# or a single file / filter:
php artisan test --compact --filter=RatingModeration
```

`composer test` runs the full gate: `config:clear`, a Pint style check
(`lint:check`), then the test suite.

## Admin access

There is **no self-registration**. Admin access is a boolean `is_admin` flag on
the `users` table, checked by `User::canAccessPanel()` (the Filament
`FilamentUser` contract). Provision or revoke access from the CLI:

```bash
php artisan app:make-admin you@example.com          # grant
php artisan app:make-admin you@example.com --revoke  # revoke
```

The user must already exist (create one via a seeder/factory or tinker); the
command only flips the flag. Anyone without `is_admin` is rejected at the panel
door.

## Moderation model

Both `companies.status` and `ratings.status` are plain strings:
`pending` | `approved` | `rejected`.

- New submissions (companies created via the rating wizard, and every public
  rating) start as **pending**.
- The public side **only ever sees `approved` records** — enforced by
  `Company::scopeApproved()`, `Rating::scopeApproved()`, and
  `Company::approvedRatings()`.
- Admins review the queue in the Filament panel: pending counts surface as
  navigation badges and status tabs, and rows can be approved/rejected in one
  click (individually or in bulk).

## Project principles

Two short docs capture the conventions this codebase is held to. Read them
before making changes, and extend the existing patterns rather than forking new
ones:

- [`docs/ux-principles.md`](docs/ux-principles.md) — the UX doctrine (RTL-first,
  moderation ergonomics, empty states, progressive disclosure, feedback).
- [`docs/code-principles.md`](docs/code-principles.md) — the code/abstraction
  rules (one source of truth per concern, thin Filament resources, frozen public
  contracts, the `is_admin` gate, test-per-change).
