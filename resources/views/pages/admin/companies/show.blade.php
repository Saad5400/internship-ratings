<?php

use App\Enums\CompanyType;
use App\Livewire\Concerns\ModeratesRecords;
use App\Models\Company;
use App\Support\ModerationStatus;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/*
 * Company workspace, not an edit form: moderate the company and its ratings
 * from one screen. Edits are a light dialog; delete is the top rung of the
 * destructive ladder, with the ratings cascade spelled out. Admins see
 * companies of any status here — there is no approved-only gate.
 */
new #[Layout('layouts.admin')] class extends Component
{
    use ModeratesRecords;

    public Company $company;

    public int $ratingsLimit = 5;

    protected int $ratingsPageSize = 5;

    /** Edit-company dialog state, prefilled from the record. */
    public ?string $editName = null;

    public ?string $editType = null;

    public ?string $editWebsite = null;

    public ?string $editDescription = null;

    public string $editStatus = 'pending';

    public function mount(Company $company): void
    {
        $this->company = $company;
        $this->fillEditForm();
    }

    public function rendering($view): void
    {
        $view->title($this->company->name);
    }

    public function updateCompany(): void
    {
        $this->normalizeEditForm();

        $this->validate([
            'editName' => ['required', 'string', 'max:255'],
            'editType' => ['nullable', 'string', Rule::in(CompanyType::values())],
            'editWebsite' => ['nullable', 'string', 'url', 'max:255'],
            'editDescription' => ['nullable', 'string', 'max:2000'],
            'editStatus' => ['required', 'string', Rule::in(array_keys(ModerationStatus::options()))],
        ], [
            'editName.required' => 'اسم الجهة مطلوب.',
            'editName.max' => 'اسم الجهة طويل جداً.',
            'editType.in' => 'نوع الجهة غير صالح.',
            'editWebsite.url' => 'يرجى إدخال رابط صحيح يبدأ بـ http أو https.',
            'editWebsite.max' => 'رابط الموقع طويل جداً.',
            'editDescription.max' => 'الوصف طويل جداً (الحد الأقصى 2000 حرف).',
            'editStatus.required' => 'حالة الجهة مطلوبة.',
            'editStatus.in' => 'حالة الجهة غير صالحة.',
        ]);

        $this->company->update([
            'name' => $this->editName,
            'type' => $this->editType,
            'website' => $this->editWebsite,
            'description' => $this->editDescription,
            'status' => $this->editStatus,
        ]);

        $this->company->refresh();
        $this->fillEditForm();
        $this->resetValidation();
        unset($this->duplicateOf, $this->approvedStats);

        $this->dispatch('close-dialog', id: 'edit-company');
        $this->dispatch('toast', message: 'تم تحديث الجهة', type: 'success');
    }

    /**
     * Deleting is permanent and cascades to the ratings at the database level
     * (ratings.company_id is cascadeOnDelete) — the confirm dialog states it.
     */
    public function deleteCompany(): void
    {
        $this->company->delete();

        session()->flash('toast', 'تم حذف الجهة');

        $this->redirect(route('admin.companies.index'), navigate: true);
    }

    public function loadMoreRatings(): void
    {
        $this->ratingsLimit += $this->ratingsPageSize;
        unset($this->ratings);
    }

    protected function afterModeration(): void
    {
        $this->company->refresh();
        $this->fillEditForm();
        unset($this->ratings, $this->ratingsTotal, $this->approvedStats, $this->duplicateOf);
    }

    /** All of the company's ratings, any status, newest first. */
    #[Computed]
    public function ratings()
    {
        return $this->company->ratings()
            ->with('company')
            ->latest()
            ->latest('id')
            ->take($this->ratingsLimit)
            ->get();
    }

    #[Computed]
    public function ratingsTotal(): int
    {
        return $this->company->ratings()->count();
    }

    /**
     * @return array{count: int, average: ?float}
     */
    #[Computed]
    public function approvedStats(): array
    {
        $average = $this->company->approvedRatings()->avg('overall_rating');

        return [
            'count' => $this->company->approvedRatings()->count(),
            'average' => $average !== null ? round((float) $average, 1) : null,
        ];
    }

    /** The approved company this pending one likely duplicates, if any. */
    #[Computed]
    public function duplicateOf(): ?Company
    {
        return $this->company->status === 'pending' ? $this->company->findSimilarApproved() : null;
    }

    /**
     * @return array<int, array{id: string, name: string}>
     */
    #[Computed]
    public function typeOptions(): array
    {
        return collect(CompanyType::options())
            ->map(fn (string $label, string $value): array => ['id' => $value, 'name' => $label])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id: string, name: string}>
     */
    #[Computed]
    public function statusOptions(): array
    {
        return collect(ModerationStatus::options())
            ->map(fn (string $label, string $value): array => ['id' => $value, 'name' => $label])
            ->values()
            ->all();
    }

    protected function fillEditForm(): void
    {
        $this->editName = $this->company->name;
        $this->editType = $this->company->type?->value;
        $this->editWebsite = $this->company->website;
        $this->editDescription = $this->company->description;
        $this->editStatus = (string) $this->company->status;
    }

    /** Blank strings from the dialog inputs become nulls before validation. */
    protected function normalizeEditForm(): void
    {
        foreach (['editName', 'editType', 'editWebsite', 'editDescription'] as $field) {
            $value = trim((string) $this->{$field});
            $this->{$field} = $value === '' ? null : $value;
        }
    }
}; ?>

<div class="space-y-8">
    @php
        $inputClass = 'w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm text-slate-900 shadow-xs transition-all focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none';
        $host = $company->website ? (parse_url($company->website, PHP_URL_HOST) ?: $company->website) : null;
        $confirmReject = $this->ratingsTotal > 0
            ? 'لدى هذه الجهة '.$this->ratingsTotal.' تقييم؛ برفضها ستُخفى هي وتقييماتها من الموقع. يمكنك أولاً نقل التقييمات إلى جهة أخرى من بطاقة كل تقييم.'
            : null;
        $deleteMessage = $this->ratingsTotal > 0
            ? 'سيؤدي حذف «'.$company->name.'» إلى إزالتها نهائياً من قاعدة البيانات مع حذف '.$this->ratingsTotal.' تقييم مرتبط بها نهائياً معها. لا يمكن التراجع عن هذا الإجراء.'
            : 'سيؤدي حذف «'.$company->name.'» إلى إزالتها نهائياً من قاعدة البيانات. لا توجد تقييمات مرتبطة بها. لا يمكن التراجع عن هذا الإجراء.';
    @endphp

    {{-- Header: identity + approved stats + the full action set --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">{{ $company->name }}</h1>
            <div class="mt-2.5 flex flex-wrap items-center gap-2 text-sm text-slate-500">
                <x-public.status-badge :status="$company->status" />
                @if($company->type)
                    <x-badge>{{ $company->type->label() }}</x-badge>
                @endif
                @if($company->website)
                    <a href="{{ $company->website }}" target="_blank" rel="noopener nofollow" dir="ltr"
                        class="inline-flex max-w-56 items-center gap-1 truncate text-xs font-medium text-blue-500 transition-colors hover:text-blue-600">
                        <svg class="size-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                        <span class="truncate">{{ $host }}</span>
                    </a>
                @endif
                <span class="text-xs text-slate-400" title="{{ $company->created_at->format('Y-m-d H:i') }}">
                    أُضيفت {{ $company->created_at->diffForHumans() }}
                </span>
            </div>
            <p class="mt-2 text-slate-500">
                @if($this->approvedStats['count'] > 0)
                    متوسط {{ number_format($this->approvedStats['average'], 1) }} من {{ $this->approvedStats['count'] }} تقييم معتمد
                @else
                    لا توجد تقييمات معتمدة بعد
                @endif
            </p>
        </div>

        <div class="flex shrink-0 flex-wrap items-center gap-2">
            <x-admin.moderation-actions :record="$company" type="company" :confirm-reject="$confirmReject" />

            <x-admin.dialog id="edit-company" title="تعديل الجهة">
                <x-slot:trigger>
                    <button type="button" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 shadow-xs transition-all hover:bg-slate-50 active:scale-[0.98]">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        تعديل
                    </button>
                </x-slot:trigger>

                <form wire:submit="updateCompany" class="space-y-4">
                    <x-public.form-field label="اسم الجهة" name="editName" required>
                        <input type="text" id="editName" wire:model="editName" class="{{ $inputClass }}" />
                    </x-public.form-field>

                    <x-public.form-field label="النوع" name="editType">
                        <x-public.nice-select
                            name="editType"
                            wire:model="editType"
                            :options="$this->typeOptions"
                            placeholder="اختر النوع..."
                            offline
                        />
                    </x-public.form-field>

                    <x-public.form-field label="الموقع الإلكتروني" name="editWebsite">
                        <input type="url" id="editWebsite" wire:model="editWebsite" dir="ltr" placeholder="https://example.com" class="{{ $inputClass }} text-start" />
                    </x-public.form-field>

                    <x-public.form-field label="الوصف" name="editDescription">
                        <textarea id="editDescription" wire:model="editDescription" rows="3" placeholder="نبذة مختصرة عن الجهة" class="{{ $inputClass }}"></textarea>
                    </x-public.form-field>

                    <x-public.form-field label="الحالة" name="editStatus" required>
                        <x-public.nice-select
                            name="editStatus"
                            wire:model="editStatus"
                            :options="$this->statusOptions"
                            offline
                            :clearable="false"
                        />
                    </x-public.form-field>

                    <div class="flex items-center justify-end gap-2 pt-1">
                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            wire:target="updateCompany"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-blue-500 px-4 py-2.5 text-sm font-semibold text-white shadow-xs transition-all hover:bg-blue-600 active:scale-[0.98] disabled:opacity-60"
                        >
                            <svg wire:loading wire:target="updateCompany" class="size-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                            حفظ التعديلات
                        </button>
                    </div>
                </form>
            </x-admin.dialog>

            <x-admin.confirm-dialog
                title="حذف الجهة"
                :message="$deleteMessage"
                confirm-label="حذف نهائياً"
                wire-click="deleteCompany"
            >
                <x-slot:trigger>
                    <button type="button" class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 bg-white px-3 py-2 text-sm font-medium text-red-600 shadow-xs transition-all hover:bg-red-50 active:scale-[0.98]">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        حذف
                    </button>
                </x-slot:trigger>
            </x-admin.confirm-dialog>
        </div>
    </div>

    {{-- Duplicate-company hint for the moderation workflow --}}
    @if($this->duplicateOf !== null)
        <div class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-amber-200 bg-amber-50/70 px-4 py-3 text-sm text-amber-800">
            <span class="inline-flex items-center gap-2 font-medium">
                <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                قد تكون نسخة مكررة من «{{ $this->duplicateOf->name }}»
            </span>
            <a href="{{ route('admin.companies.show', $this->duplicateOf) }}" wire:navigate class="font-semibold text-amber-900 underline-offset-2 hover:underline">
                عرض الجهة الأصلية
            </a>
        </div>
    @endif

    @if($company->description)
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs">
            <h2 class="text-sm font-semibold text-slate-500">الوصف</h2>
            <p class="mt-2 text-sm leading-relaxed text-slate-700">{{ $company->description }}</p>
        </div>
    @endif

    {{-- All the company's ratings, any status, with inline moderation --}}
    <section aria-labelledby="company-ratings-heading" class="space-y-4">
        <h2 id="company-ratings-heading" class="flex items-center gap-2 text-lg font-bold text-slate-900">
            التقييمات
            <x-badge>{{ $this->ratingsTotal }}</x-badge>
        </h2>

        @if($this->ratingsTotal === 0)
            <x-admin.empty-state
                icon="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.196-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.783-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"
                message="لا توجد تقييمات لهذه الجهة بعد — تقييمات الزوار الجديدة ستظهر هنا فور وصولها"
            >
                <a href="{{ route('admin.companies.index') }}" wire:navigate class="text-sm font-medium text-blue-500 transition-colors hover:text-blue-600">
                    العودة إلى قائمة الجهات
                </a>
            </x-admin.empty-state>
        @else
            <div class="mx-auto max-w-3xl space-y-4">
                @foreach($this->ratings as $rating)
                    <div wire:key="company-rating-{{ $rating->id }}">
                        <x-admin.rating-review-card :rating="$rating" :reassigning="$reassignRatingId === $rating->id" />
                    </div>
                @endforeach

                @if($this->ratingsTotal > $this->ratings->count())
                    <button
                        type="button"
                        wire:click="loadMoreRatings"
                        wire:loading.attr="disabled"
                        wire:target="loadMoreRatings"
                        class="flex w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-600 shadow-xs transition-colors hover:bg-slate-50 disabled:opacity-60"
                    >
                        <svg wire:loading wire:target="loadMoreRatings" class="size-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                        عرض المزيد ({{ $this->ratingsTotal - $this->ratings->count() }} متبقٍ)
                    </button>
                @endif
            </div>
        @endif
    </section>
</div>
