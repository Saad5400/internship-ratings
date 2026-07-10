# Code principles

How this codebase is structured and the rules new code is held to. The theme is
**one source of truth per concern** and **extend the existing abstraction, never
fork a pattern**. Every rule points at real files — imitate them.

## 1. One source of truth per concern

- **Enums own their own display + choices.** `app/Enums/*` (`CompanyType`,
  `Modality`, `Recommendation`, `ReviewerDegree`, `SaudiCity`) each expose
  `label()`, `options()`, `values()` — plus `color()` where the value is
  badge-rendered. Never re-map an enum's cases to Arabic labels or colors in a
  view/resource; call the enum. Cast the column to the enum on the model
  (`Rating::casts()` maps `modality`, `recommendation`, `reviewer_degree`;
  `Company::casts()` maps `type`).
- **Moderation status has one helper.** `App\Support\ModerationStatus`
  (`label()`, `color()`, `options()`) is the single source for the
  `pending/approved/rejected` display vocabulary. Use it everywhere status is
  shown or filtered. (Status is a plain string column, not an enum; a handful of
  older files still inline the same `match()` — migrate them to
  `ModerationStatus` when you touch them, don't add new inline copies.)
- **Models own scopes and query rules.** The "public only sees approved" rule
  lives in one layer: `Company::scopeApproved()` / `scopePending()`,
  `Company::approvedRatings()`, `Rating::scopeApproved()`. Query through these
  scopes instead of re-writing `where('status', 'approved')` inline.
- **Domain math lives on the model.** The weighted overall rating and its
  derived recommendation are computed once, in `Rating`: `metricWeights()`,
  `calculateOverallRating()`, `recommendationFromOverall()`, wired through a
  `saving()` hook — so every write path (public wizard, admin form, seeder) gets
  identical results. The promoter/passive thresholds are named constants
  (`RECOMMENDATION_PROMOTER_THRESHOLD`, `RECOMMENDATION_PASSIVE_THRESHOLD`).
- **Arabic search normalization has one owner.** `App\Support\Arabic::normalize()`
  strips tashkeel/tatweel and unifies letter variants. `Company` persists the
  result to `name_normalized` in its `saving()` hook and matches against it in
  `scopeSearchByName()`. Never normalize Arabic ad hoc — call `Arabic::normalize`.

## 2. Thin Filament resources delegate to Schemas / Tables

Resource classes stay small and declarative. `CompanyResource` and
`RatingResource` hold only navigation metadata, badges, global-search config,
and `getPages()` — the actual form and table are delegated:

```php
public static function form(Schema $schema): Schema  { return CompanyForm::configure($schema); }
public static function table(Table $table): Table    { return CompaniesTable::configure($table); }
```

- Form definitions live in `Resources/*/Schemas/*Form.php`, tables in
  `Resources/*/Tables/*Table.php`, list-page tabs in `Resources/*/Pages/*`.
- When adding fields/columns, edit the Schema/Table class — do not inline a form
  or table back into the resource.
- Infolists currently live inline on the resource (`CompanyResource::infolist`,
  `RatingResource::infolist`); follow that placement for consistency.

## 3. The public component layer

Public UI is composed from shared Blade components under
`resources/views/components/public/*` (`company-card`, `rating-card`,
`star-picker`, `nice-select`, `overall-score`, `rating-bar`, `status-badge`,
`count-up`, `form-field`, and matching `*-skeleton`s). Public pages are Volt
single-file components in `resources/views/pages/`.

- Reuse these components; check for an existing one before writing new markup
  (e.g. list loading states reuse `company-card-skeleton` / `rating-card-skeleton`).
- Keep Livewire state server-side and reflect shareable state in the URL with
  `#[Url]` (see the companies index `search`/`sort`).

## 4. Validation and authorization live in the right layer

- **Public input** is validated in the Volt component that owns the form — the
  rating wizard validates per step (`rulesForStep()`), with Arabic messages and
  a Turnstile rule gated on config (`config('turnstile.enabled')`).
- **Admin input** is validated by the Filament Schema field definitions.
- **Panel authorization** is a single gate: `User::canAccessPanel()` returns
  `$this->is_admin === true`. Don't scatter role checks — rely on the flag and
  the panel's `authMiddleware`.

## 5. Admin access via the `is_admin` gate + provisioning command

- Access is the boolean `users.is_admin` (cast in `User::casts()`), checked once
  in `canAccessPanel()`. There is **no public registration** — Fortify was
  removed and Filament owns admin auth.
- Grant/revoke only through `php artisan app:make-admin {email} {--revoke}`
  (`App\Console\Commands\MakeAdminCommand`). Don't invent parallel admin checks
  or ad-hoc flags.

## 6. Frozen public contracts stay stable

The public URL and SEO surface are contracts — changing them breaks links,
indexing, and structured data. Treat them as frozen unless a change is the point:

- Routes in `routes/web.php`: `home` → `/companies`, `companies.index`,
  `companies.show/{company}`, `ratings.create`, and `sitemap` (`/sitemap.xml`).
  The production `ratings.create` route carries `throttle:10,60`.
- SEO/structured data: JSON-LD graphs embedded in the index and show pages
  (`WebSite`/`Organization`/`ItemList`, `BreadcrumbList`, `AggregateRating`),
  `metaDescription` set via `rendering()`, and the sitemap that lists only
  `Company::approved()` records. Keep these intact when refactoring those pages.

## 7. Test-per-change discipline (Pest)

- Every change is programmatically tested. Feature tests live in
  `tests/Feature/*` — e.g. `AdminAccessTest`, `RatingModerationTest`,
  `CompanyTest`, `RatingTest`, `RealDataSeederTest`. Add or update a test, then
  run the affected file:

  ```bash
  php artisan test --compact --filter=RatingModeration
  ```

- Use model factories (and their states) for test data. Most tests are feature
  tests; reserve `tests/Unit` for pure logic.

## 8. Formatting and conventions

- Run `vendor/bin/pint --dirty --format agent` before finalizing PHP changes
  (the `composer test` gate runs `lint:check` and will fail on style drift).
- Follow the PHP conventions in `CLAUDE.md`: constructor property promotion,
  explicit return types and parameter type hints, curly braces on all control
  structures, `TitleCase` enum keys, PHPDoc (with array-shape types) over inline
  comments.
- Create files the Laravel/Filament way (`php artisan make:*`,
  `--no-interaction`); stick to the existing directory structure and don't add
  base folders or dependencies without approval.

## 9. Extend the existing abstraction, never fork a pattern

Before adding code, find the nearest existing example and extend it: a new
displayed value backed by a fixed set → an enum with `label()/color()/options()`;
a new query rule → a model scope; a new admin screen → a thin resource plus
Schema/Table classes; a new public block → a `components/public/*` component; a
new status surface → `ModerationStatus`. Duplicating a pattern with a slight
variation is the thing to avoid.
