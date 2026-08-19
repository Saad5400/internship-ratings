<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Digest telling an admin how many submissions await moderation.
 *
 * Counts are snapshotted at queue time — a digest is a nudge, not a live
 * dashboard, and the linked review inbox always shows the current state.
 */
class PendingModerationDigest extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $pendingRatings, public int $pendingCompanies)
    {
        /*
         * The email renders outside any request, so SetAdminLocale never runs
         * and the ambient locale may be `en` (which has no counts.* strings).
         * Pin the locale so trans_choice() in the view resolves from lang/ar.
         */
        $this->locale('ar');
    }

    public function backoff(): int
    {
        return 30;
    }

    public function envelope(): Envelope
    {
        // Same phrasing as the dashboard header; locale passed explicitly
        // because the envelope may be built outside the mailable's locale.
        $parts = array_filter([
            $this->pendingRatings > 0 ? trans_choice('counts.ratings', $this->pendingRatings, [], 'ar') : null,
            $this->pendingCompanies > 0 ? trans_choice('counts.companies', $this->pendingCompanies, [], 'ar') : null,
        ]);

        return new Envelope(
            subject: 'بانتظارك '.implode(' و', $parts).' للمراجعة',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.moderation-digest',
            with: [
                'dashboardUrl' => route('admin.dashboard'),
            ],
        );
    }
}
