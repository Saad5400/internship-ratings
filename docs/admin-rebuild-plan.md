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
- [x] **2. Shared admin component layer** — `components/admin/*` (page-header,
  stat-card, score-badge, empty-state, toast-host with undo,
  moderation-actions, review cards) + shared `<x-badge>` semantic-color map;
  `ModeratesRecords` trait; public status-badge migrated to `ModerationStatus`.
- [x] **3. Review inbox dashboard** — pending ratings/companies as rich inline
  cards (the exact public rating card wrapped with company context), one-click
  approve/reject with undo toasts, stats + distribution + top companies below
  the queue.
- [x] **3b. Moderation power flows** (user-requested):
  - *Reassign a rating's company* — inline searchable company picker on the
    rating card/edit, for the duplicate-company case; plus a proactive
    suggestion ("same as approved «X»? move the rating") powered by
    `name_normalized` similarity, so approve-rating-then-reject-duplicate is a
    two-click flow.
  - *Reject/delete a company that has ratings* — consequence-count confirm
    dialog (destructive-action ladder rung 2), offering to move ratings first.
  - *Duplicate warning at approve time* — approving a company similar to an
    existing approved one warns and suggests merging instead.
- [x] **4. Ratings workspace** — list (tabs/filters/search/sort), detail page,
  edit form with progressive disclosure. Model stays the source of
  `overall_rating` / derived recommendation.
- [x] **5. Companies workspace** — list, detail with inline ratings tab
  (replaces the relation manager), edit, approve/reject.
- [x] **6. Users** — list + create/edit; keep: hashed password, blank-password
  keeps hash, unique email, cannot delete self, cannot demote/delete last admin.
  *Invite flow* (user-requested): add an admin with name+email only → the app
  issues a temporary **signed setup link** (copy button, no mailer dependency);
  the invitee opens it, sets their own password, and is signed in. Same
  machinery reused as a "reset password link" action for existing admins.
  Manual password entry stays as a collapsed advanced option.
- [x] **7. Global search (Ctrl/Cmd-K) + polish** — command palette across
  companies/ratings/users, mobile pass, a11y pass.
- [x] **8. Cleanup** — delete `app/Filament`, Filament deps, provider, parked
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
| Moderation queue, 1-click approve/reject | table row/bulk actions | review inbox (phase 3) | ✅ |
| Ratings list: status tabs + counts | `ListRatings` 7 tabs | phase 4 (tabs simplified) | ✅ |
| Ratings filters (status/modality/recommendation/company type/score/job offer/supervisor) | `RatingsTable` | phase 4 | ✅ |
| Rating create/edit (validation per `RatingForm`) | `RatingForm` | phase 4 | ✅ |
| Rating detail (all sections, empty-state fallbacks) | `RatingResource::infolist` | phase 4 | ✅ |
| Companies list: tabs, filters, avg/count columns | `CompaniesTable` | phase 5 | ✅ |
| Company create/edit | `CompanyForm` | phase 5 | ✅ |
| Company detail + its ratings (approve/reject inline) | infolist + `RatingsRelationManager` | phase 5 | ✅ |
| Users CRUD + protections | `UserResource` | phase 6 | ✅ |
| Dashboard stats (6 cards), top companies, distribution | widgets | phase 3 | ✅ |
| Global search Ctrl/Cmd-K | panel global search | phase 7 command palette | ✅ |
| Deliberately dropped | DB notifications bell (never dispatched to), per-column show/hide toggling | — | 🚫 |
