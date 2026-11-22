<?php

namespace App\Livewire\Concerns;

/**
 * Re-checks admin access on every Livewire request, not just the page load.
 *
 * The `/admin` routes sit behind EnsureUserIsAdmin, but a component's later
 * actions arrive on Livewire's own update endpoint, which re-runs only
 * Livewire's persistent middleware (auth, auth.session, …) — not ours. Without
 * this, an admin whose `is_admin` flag was revoked keeps full use of every admin
 * page they already had open (or saved a snapshot of): approve, delete, grant
 * admin back to themselves. Livewire calls `boot{TraitName}` on mount and on
 * every subsequent update, so the check can't be skipped.
 */
trait RequiresAdmin
{
    public function bootRequiresAdmin(): void
    {
        abort_unless(auth()->user()?->is_admin === true, 403);
    }
}
