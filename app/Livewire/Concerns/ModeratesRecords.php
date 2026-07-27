<?php

namespace App\Livewire\Concerns;

use App\Models\Company;
use App\Models\Rating;
use App\Support\ModerationStatus;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\On;

/**
 * Shared moderation behavior for admin pages: one-click approve/reject with an
 * undo toast. Status changes are reversible, so they get no confirm dialog —
 * the undo action in the toast is the guard (see docs/ux-principles.md §8).
 */
trait ModeratesRecords
{
    public function moderate(string $type, int $id, string $status): void
    {
        abort_unless(array_key_exists($status, ModerationStatus::options()), 400);

        $record = $this->findModeratable($type, $id);
        $previous = (string) $record->getAttribute('status');

        if ($previous === $status) {
            return;
        }

        $record->update(['status' => $status]);

        $this->dispatch(
            'toast',
            message: $status === 'approved' ? 'تمت الموافقة' : 'تم الرفض',
            type: 'success',
            undoEvent: 'moderation-undo',
            undoPayload: ['type' => $type, 'id' => $id, 'previous' => $previous],
        );

        $this->afterModeration();
    }

    #[On('moderation-undo')]
    public function undoModeration(string $type, int $id, string $previous): void
    {
        abort_unless(array_key_exists($previous, ModerationStatus::options()), 400);

        $this->findModeratable($type, $id)->update(['status' => $previous]);

        $this->afterModeration();
    }

    protected function findModeratable(string $type, int $id): Model
    {
        $class = match ($type) {
            'rating' => Rating::class,
            'company' => Company::class,
            default => abort(400),
        };

        return $class::query()->findOrFail($id);
    }

    /**
     * Hook for pages to refresh computed state after a moderation change.
     */
    protected function afterModeration(): void {}
}
