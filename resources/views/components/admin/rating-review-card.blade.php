{{--
    A rating as reviewed in the moderation queue: the full public rating card
    (exactly what readers would see) wrapped with company context on top and
    moderation actions below. Consuming page must use ModeratesRecords.

    Duplicate-company workflow: when the rating's company is itself pending and
    looks like a duplicate of an approved company, a one-click "move the rating
    there" suggestion appears; a manual "نقل" picker covers every other case.
--}}
@props(['rating', 'reassigning' => false])

@php
    /** @var \App\Models\Company|null $suggestedCompany */
    $suggestedCompany = $rating->company?->status === 'pending' ? $rating->company->findSimilarApproved() : null;
@endphp

<article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 bg-slate-50/60 px-4 py-3 sm:px-6">
        <div class="flex min-w-0 items-center gap-2.5">
            <svg class="size-5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            <span class="truncate font-semibold text-slate-900">{{ $rating->company?->name ?? 'جهة غير معروفة' }}</span>
            @if($rating->company?->status === 'pending')
                <x-public.status-badge status="pending" title="هذه الجهة نفسها بانتظار المراجعة" />
            @endif
            @if(! $reassigning)
                <button
                    type="button"
                    wire:click="startReassign({{ $rating->id }})"
                    class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs font-medium text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-900"
                    title="نقل التقييم إلى جهة أخرى"
                >
                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                    نقل
                </button>
            @endif
        </div>
        <x-public.status-badge :status="$rating->status" />
    </div>

    @if($reassigning)
        <div class="border-b border-slate-100 bg-blue-50/50 px-4 py-3 sm:px-6">
            <p class="text-xs font-medium text-slate-600">نقل هذا التقييم إلى جهة أخرى:</p>
            <div class="mt-2 flex flex-wrap items-center gap-2">
                <div class="min-w-64 flex-1">
                    <x-public.nice-select
                        name="reassignCompanyId"
                        wire:model="reassignCompanyId"
                        :options="$this->reassignCompanyOptions"
                        placeholder="اختر الجهة..."
                        offline
                        searchable
                        aria-label="الجهة الجديدة للتقييم"
                    />
                </div>
                <button
                    type="button"
                    wire:click="confirmReassign"
                    wire:loading.attr="disabled"
                    wire:target="confirmReassign"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-blue-500 px-3 py-2 text-sm font-medium text-white shadow-xs transition-all hover:bg-blue-600 active:scale-[0.98] disabled:opacity-60"
                >
                    نقل التقييم
                </button>
                <button
                    type="button"
                    wire:click="cancelReassign"
                    class="rounded-lg px-3 py-2 text-sm font-medium text-slate-500 transition-colors hover:bg-slate-100"
                >
                    إلغاء
                </button>
            </div>
        </div>
    @elseif($suggestedCompany !== null)
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 bg-amber-50/70 px-4 py-2.5 text-xs text-amber-800 sm:px-6">
            <span class="inline-flex items-center gap-1.5 font-medium">
                <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                «{{ $rating->company->name }}» قد تكون نسخة مكررة من «{{ $suggestedCompany->name }}»
            </span>
            <button
                type="button"
                wire:click="reassignRating({{ $rating->id }}, {{ $suggestedCompany->id }})"
                wire:loading.attr="disabled"
                wire:target="reassignRating({{ $rating->id }}, {{ $suggestedCompany->id }})"
                class="inline-flex items-center gap-1 rounded-lg bg-amber-600 px-2.5 py-1.5 font-semibold text-white shadow-xs transition-all hover:bg-amber-700 active:scale-[0.98] disabled:opacity-60"
            >
                نقل التقييم إليها
            </button>
        </div>
    @endif

    <div class="p-1 sm:p-2">
        {{-- The exact card readers see publicly; contact is always revealed for moderators --}}
        <x-public.rating-card :rating="$rating" :contact-revealed="true" :votable="false" class="!border-0 !shadow-none" />
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 bg-slate-50/60 px-4 py-3 sm:px-6">
        <x-admin.moderation-actions :record="$rating" type="rating" />
        @if(\Illuminate\Support\Facades\Route::has('admin.ratings.edit'))
            <a href="{{ route('admin.ratings.edit', $rating) }}" wire:navigate
                class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-100 hover:text-slate-900">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                تعديل
            </a>
        @endif
    </div>
</article>
