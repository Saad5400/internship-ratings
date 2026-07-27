<?php

namespace App\Livewire\Concerns;

use App\Models\Company;
use App\Models\Rating;
use App\Support\ModerationStatus;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

/**
 * Shared moderation behavior for admin pages: one-click approve/reject with an
 * undo toast. Status changes are reversible, so they get no confirm dialog —
 * the undo action in the toast is the guard (see docs/ux-principles.md §8).
 */
trait ModeratesRecords
{
    /** The rating whose company is currently being reassigned, if any. */
    public ?int $reassignRatingId = null;

    public ?int $reassignCompanyId = null;

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

    /**
     * Move a rating to another company — the duplicate-company workflow:
     * reassign the rating to the real entry, then reject the duplicate.
     */
    public function reassignRating(int $ratingId, int $companyId): void
    {
        $rating = Rating::query()->findOrFail($ratingId);
        $target = Company::query()->findOrFail($companyId);
        $previousCompanyId = (int) $rating->company_id;

        if ($previousCompanyId === $target->id) {
            return;
        }

        $rating->update(['company_id' => $target->id]);

        $this->dispatch(
            'toast',
            message: 'تم نقل التقييم إلى «'.$target->name.'»',
            type: 'success',
            undoEvent: 'reassign-undo',
            undoPayload: ['ratingId' => $rating->id, 'companyId' => $previousCompanyId],
        );

        $this->afterModeration();
    }

    public function startReassign(int $ratingId): void
    {
        $this->reassignRatingId = $ratingId;
        $this->reassignCompanyId = null;
    }

    public function cancelReassign(): void
    {
        $this->reassignRatingId = null;
        $this->reassignCompanyId = null;
    }

    public function confirmReassign(): void
    {
        if ($this->reassignRatingId === null || $this->reassignCompanyId === null) {
            return;
        }

        $this->reassignRating($this->reassignRatingId, $this->reassignCompanyId);
        $this->cancelReassign();
    }

    /**
     * All companies as picker options for the reassign flow.
     *
     * @return array<int, array{id: int, name: string}>
     */
    #[Computed]
    public function reassignCompanyOptions(): array
    {
        return Company::query()
            ->orderBy('name')
            ->get(['id', 'name', 'status'])
            ->map(fn (Company $company) => [
                'id' => $company->id,
                'name' => $company->status === 'approved' ? $company->name : $company->name.' ('.ModerationStatus::label($company->status).')',
            ])
            ->all();
    }

    #[On('reassign-undo')]
    public function undoReassignRating(int $ratingId, int $companyId): void
    {
        Rating::query()->findOrFail($ratingId)->update([
            'company_id' => Company::query()->findOrFail($companyId)->id,
        ]);

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
