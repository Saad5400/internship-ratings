# Admin panel rebuild — plan & feature inventory

Living document for the Filament → Livewire 4 migration on branch
`admin-livewire-rebuild`. Deleted (or folded into the README) when the
migration completes.

**Goal:** replace the Filament panel with a Livewire 4 + Tailwind panel built
from the same stack and component philosophy as the public app — and make it
meaningfully *better to use*, not just equivalent. UX doctrine:
[ux-principles.md](ux-principles.md); code doctrine:
[code-principles.md](code-principles.md).

**Decisions (settled, do not re-litigate):**

- UI is hand-rolled Tailwind/daisyUI in `components/admin/*`, extending the
  `components/public/*` style. No Flux, no Filament UI.
- Big-bang on this branch: the new panel owns `/admin`; Filament is parked at
  `/filament` and deleted in the final phase.
- Redesign freedom: every real capability survives (moderation, filters, bulk
  actions, search, self-protection rules), unused chrome does not (database
  notifications bell, per-column show/hide).
- Auth is a minimal hand-rolled Livewire login at `/admin/login`
  (admin-only `Auth::attempt`, rate-limited, session-regenerating). Admins are
  provisioned only via `php artisan app:make-admin`.

## Phases

- [x] **1. Foundation** — `EnsureUserIsAdmin` middleware, `routes/admin.php`,
  `layouts/admin` (topbar nav + mobile bottom tab bar, thumb zone), login page,
  dashboard shell, Filament parked at `/filament`. Tests: `AdminLoginTest`.
- [ ] **2. Shared admin component layer** — `components/admin/*`: page header,
  stat card, data-table primitives (sortable header, status tabs with counts,
  filter row, pagination), score badge (threshold colors centralized), confirm
  dialog, toast bridge, empty states; migrate remaining inline status `match()`
  arms to `ModerationStatus`.
- [ ] **3. Review inbox dashboard** — the front door is the moderation queue:
  pending ratings/companies as rich inline cards (full context, no drill-in),
  one-click approve/reject, keyboard next/prev, stats row + top companies +
  distribution below the queue.
- [ ] **4. Ratings workspace** — list (tabs/filters/search/sort), detail page,
  edit form with progressive disclosure. Model stays the source of
  `overall_rating` / derived recommendation.
- [ ] **5. Companies workspace** — list, detail with inline ratings tab
  (replaces the relation manager), edit, approve/reject.
- [ ] **6. Users** — list + create/edit; keep: hashed password, blank-password
  keeps hash, unique email, cannot delete self, cannot demote/delete last admin.
- [ ] **7. Global search (Ctrl/Cmd-K) + polish** — command palette across
  companies/ratings/users, mobile pass, a11y pass.
- [ ] **8. Cleanup** — delete `app/Filament`, Filament deps, provider, parked
  routes; rewrite remaining Filament-coupled tests; update README + docs.

## Feature inventory (parity checklist)

Frozen behavior (tests enforce): only-approved is public; public submissions
are pending; approve = publish; `is_admin` is the only gate; make-admin
command; last-admin demotion/deletion protection; self-deletion protection;
blank password on edit keeps the hash.

| Capability | Filament source | New home | Status |
| --- | --- | --- | --- |
| Admin login (RTL, Arabic) | panel `->login()` | `pages/admin/login` | ✅ phase 1 |
| `is_admin` gate → 403 | `canAccessPanel()` | `EnsureUserIsAdmin` | ✅ phase 1 |
| Pending-count nav badges | resource nav badges | layout nav badge (المراجعة) | ✅ phase 1 |
| Moderation queue, 1-click approve/reject | table row/bulk actions | review inbox (phase 3) | ⬜ |
| Ratings list: status tabs + counts | `ListRatings` 7 tabs | phase 4 (tabs simplified) | ⬜ |
| Ratings filters (status/modality/recommendation/company type/score/job offer/supervisor) | `RatingsTable` | phase 4 | ⬜ |
| Rating create/edit (validation per `RatingForm`) | `RatingForm` | phase 4 | ⬜ |
| Rating detail (all sections, empty-state fallbacks) | `RatingResource::infolist` | phase 4 | ⬜ |
| Companies list: tabs, filters, avg/count columns | `CompaniesTable` | phase 5 | ⬜ |
| Company create/edit | `CompanyForm` | phase 5 | ⬜ |
| Company detail + its ratings (approve/reject inline) | infolist + `RatingsRelationManager` | phase 5 | ⬜ |
| Users CRUD + protections | `UserResource` | phase 6 | ⬜ |
| Dashboard stats (6 cards), top companies, distribution | widgets | phase 3 | ⬜ |
| Global search Ctrl/Cmd-K | panel global search | phase 7 command palette | ⬜ |
| Deliberately dropped | DB notifications bell (never dispatched to), per-column show/hide toggling | — | 🚫 |
