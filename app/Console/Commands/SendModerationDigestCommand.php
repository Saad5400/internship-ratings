<?php

namespace App\Console\Commands;

use App\Mail\PendingModerationDigest;
use App\Models\Company;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

#[Signature('moderation:digest {--force : Send even if a digest went out within the last 48 hours}')]
#[Description('Email admins a digest of submissions awaiting moderation, at most every two days.')]
class SendModerationDigestCommand extends Command
{
    /*
     * The schedule runs this daily; the every-two-days cadence lives here, in
     * a cache guard, rather than in a `* / 2` day-of-month cron (which
     * double-fires across month boundaries and can't recover a missed run
     * until the next matching day). The TTL is 47h, not 48h, so the run two
     * mornings later isn't skipped by clock-boundary equality. The guard is
     * written only when a digest is actually sent — a quiet day never
     * consumes it.
     */
    private const LAST_SENT_CACHE_KEY = 'moderation-digest:sent';

    private const GUARD_HOURS = 47;

    public function handle(): int
    {
        $pendingRatings = Rating::pending()->count();
        $pendingCompanies = Company::pending()->count();

        if ($pendingRatings + $pendingCompanies === 0) {
            $this->components->info('Nothing is pending moderation; no digest sent.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && Cache::has(self::LAST_SENT_CACHE_KEY)) {
            $this->components->info('A digest was already sent within the last 48 hours; skipping.');

            return self::SUCCESS;
        }

        $admins = User::admins()->get();

        if ($admins->isEmpty()) {
            $this->components->warn('No admin users exist; no digest sent.');

            return self::SUCCESS;
        }

        foreach ($admins as $admin) {
            // One mail per admin: no shared To/BCC leaking admin addresses.
            Mail::to($admin)->send(new PendingModerationDigest($pendingRatings, $pendingCompanies));
        }

        Cache::put(self::LAST_SENT_CACHE_KEY, now()->toIso8601String(), now()->addHours(self::GUARD_HOURS));

        $this->components->info(sprintf(
            'Digest queued for %d admin(s): %d pending rating(s), %d pending company(ies).',
            $admins->count(),
            $pendingRatings,
            $pendingCompanies,
        ));

        return self::SUCCESS;
    }
}
