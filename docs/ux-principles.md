# UX principles

The UX doctrine this app follows, as a checklist. Every principle is grounded in
something already shipped here — when you add UI, match the nearest existing
example rather than inventing a new pattern.

## 1. RTL and Arabic are first-class, not a skin

- The public shell declares `<html lang="ar" dir="rtl">`
  (`resources/views/layouts/public.blade.php`); the Filament panel is Arabic
  end-to-end (`brandName('تقييم التدريب')`, Arabic navigation groups, model
  labels, tabs).
- Use **logical CSS properties**, never physical ones: `ps-*/pe-*`, `ms-*/me-*`,
  `start-0/end-0`, `border-s-*`. The companies index search box
  (`ps-11 pe-11`, `inset-y-0 start-0`, clear button at `end-0`) is the reference.
- Every user-facing string is Arabic. Labels, placeholders, empty-state copy,
  validation messages (`turnstile.required => 'يرجى إكمال التحقق الأمني.'`),
  and `aria-label`s are all authored in Arabic.
- Checklist: no `pl-/pr-/ml-/mr-/left-/right-` in new markup; no English strings
  leaking into the UI; new admin resources set `navigationLabel`, `modelLabel`,
  `pluralModelLabel`.

## 2. Frequency drives prominence

The daily job is clearing the moderation queue, so that action is the most
prominent thing in the panel; rare configuration is buried in edit pages.

- Pending counts are surfaced as **navigation badges** with tooltips
  (`CompanyResource::getNavigationBadge()` /
  `RatingResource::getNavigationBadge()`, colored `warning`).
- The ratings list opens onto **status tabs** (`ListRatings::getTabs()`):
  الكل / قيد المراجعة / موافق عليها / مرفوضة plus quick views (موصى بها, تقييم
  عالي), each with its own count badge.
- Approve / reject are **one-click row actions** (and bulk actions) in
  `RatingsTable` and the companies `RatingsRelationManager` — no drilling into an
  edit page to change a status.
- Checklist: the common action is reachable in one click from a list; rare
  settings live behind View/Edit.

## 3. One status vocabulary, presented consistently

`pending | approved | rejected` always renders the same way everywhere it
appears: same Arabic labels (قيد المراجعة / موافق عليه / مرفوض) and the same
colors (`warning` / `success` / `danger`).

- The vocabulary lives in one place: `App\Support\ModerationStatus`
  (`::label()`, `::color()`, `::options()`). Panel surfaces call it —
  e.g. `RatingsTable` and `CompanyResource`'s status column use
  `ModerationStatus::color($state)` / `::label($state)`, and the ratings status
  filter uses `ModerationStatus::options()`.
- Always render status through `ModerationStatus`; never re-hardcode the Arabic
  labels or the warning/success/danger colors. A few older call sites
  (`RatingForm`, `CompanyForm`, `RatingsRelationManager`, the public
  `status-badge` component, and the `RatingResource` infolist) still inline the
  same `match()` arms — when you touch one, migrate it to `ModerationStatus`
  rather than copying the literal strings again.
- Enum-backed values (`Recommendation`, `Modality`, `CompanyType`) already
  centralize their label + color; always call `->label()` / `->color()` /
  `::options()` instead of re-mapping cases.

## 4. Empty states teach, they don't just sit empty

- The companies index empty state
  (`resources/views/pages/companies/index.blade.php`) shows an icon, a message
  that adapts to context (`لا توجد جهات تطابق بحثك` when searching vs.
  `... حالياً` otherwise), and a **"clear search"** action to recover.
- Infolist fields declare sensible fallbacks instead of blanks:
  `->default('غير متوفر')`, `'لا يوجد وصف'`, `'مجهول'`, `'بدون مكافأة'`.
- Checklist: any list/detail that can be empty offers a reason and a next step,
  not a void.

## 5. Progressive disclosure — reveal complexity in steps

- The public rating form is a **3-step wizard**
  (`resources/views/pages/ratings/create.blade.php`) with per-step validation
  (`rulesForStep()`), a clickable step indicator, and forward-only guarded
  navigation (`goToStep()` re-validates each step it passes through).
- Optional/contextual fields appear only when relevant: the contact section is
  gated on `@if($willing_to_help === true)`; a new company's detail fields show
  only when `companyId === '__new__'`; the "أرسل تقييمك" copy frames step 3's
  extras as optional (`معلومات اختيارية تساعد القرّاء...`).
- Filament forms group fields into titled, icon-bearing `Section`s rather than
  one flat form.
- Checklist: don't dump every field at once; step or gate what isn't always
  needed.

## 6. Skeletons over spinners for content; inline spinners for micro-waits

- Infinite scroll loads more company cards behind **skeleton placeholders**
  (`<x-public.company-card-skeleton :count="2" />`) driven by
  `wire:loading.grid`, matched by `rating-card-skeleton`. Content-shaped loading,
  not a bare spinner.
- A **small inline spinner** is acceptable for a targeted micro-wait — e.g. the
  in-flight search indicator (`wire:loading wire:target="search"`) sitting inside
  the search box.
- Checklist: loading a block of content → skeleton that mirrors its shape;
  a single field refreshing → inline spinner scoped with `wire:target`.

## 7. Optimistic, inline feedback; toast discipline

- Search and sort are **live and debounced** (`wire:model.live.debounce.300ms`),
  with query state reflected in the URL (`#[Url]`) so results are shareable and
  back-button friendly.
- The wizard scrolls to top on step change via a dispatched event
  (`rating-wizard-step-changed`), keeping the user oriented.
- Reserve notifications/toasts for the outcome of an explicit admin action
  (approve/reject/delete confirmations). Don't toast on every keystroke or
  passive state change.

## 8. Destructive-action ladder — guard by severity

- Reversible status changes (approve / reject) use `->requiresConfirmation()`
  and are hidden when they'd be a no-op
  (`->visible(fn ($record) => $record->status !== 'approved')`).
- Deletes are the top rung: explicit `DeleteAction` / `DeleteBulkAction`, clearly
  labeled `حذف`, separated from the everyday approve/reject buttons.
- Public writes sit behind their own guards: **Cloudflare Turnstile** plus route
  **throttling** (`throttle:10,60` on `ratings.create` in production).
- Checklist: match friction to consequence — silent for trivial, confirm for
  state changes, confirm + distinct affordance for deletes.

## 9. Accessibility baseline

- Interactive controls carry Arabic `aria-label`s; decorative SVGs are
  `aria-hidden="true"`; the wizard step indicator sets `aria-current="step"`.
- Keep this up: new icon-only buttons need an `aria-label`, and purely
  decorative graphics must be hidden from assistive tech.
