{{--
    A company awaiting moderation. Consuming page must use ModeratesRecords.
    Expects `ratings_total_count` (withCount alias) — Company::ratings_count is
    an accessor that always returns the approved-only count.
--}}
@props(['company'])

@php
    /** @var \App\Models\Company|null $duplicateOf */
    $duplicateOf = $company->status === 'pending' ? $company->findSimilarApproved() : null;
    $linkedRatings = $company->ratings_total_count ?? $company->ratings()->count();
@endphp

<article class="flex h-full flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-xs">
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <h3 class="truncate font-semibold text-slate-900">{{ $company->name }}</h3>
            <div class="mt-1.5 flex flex-wrap items-center gap-1.5 text-xs">
                @if($company->type)
                    <x-badge>{{ $company->type->label() }}</x-badge>
                @endif
                <x-public.status-badge :status="$company->status" />
            </div>
        </div>
        @if($company->website)
            <a href="{{ $company->website }}" target="_blank" rel="noopener nofollow" dir="ltr"
                class="inline-flex max-w-40 items-center gap-1 truncate text-xs font-medium text-blue-500 transition-colors hover:text-blue-600">
                <svg class="size-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                <span class="truncate">{{ str_replace(['https://', 'http://'], '', rtrim($company->website, '/')) }}</span>
            </a>
        @endif
    </div>

    @if($duplicateOf !== null)
        <div class="mt-3 flex flex-wrap items-center justify-between gap-2 rounded-xl border border-amber-200 bg-amber-50/70 px-3 py-2.5 text-xs text-amber-800">
            <span class="inline-flex items-center gap-1.5 font-medium">
                <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                قد تكون نسخة مكررة من «{{ $duplicateOf->name }}»
            </span>
            @if(\Illuminate\Support\Facades\Route::has('admin.companies.show'))
                <a href="{{ route('admin.companies.show', $duplicateOf) }}" wire:navigate class="font-semibold text-amber-900 underline-offset-2 hover:underline">
                    عرض الجهة الأصلية
                </a>
            @endif
        </div>
    @endif

    <p class="mt-3 flex-1 text-sm leading-relaxed text-slate-600">
        {{ $company->description ?: 'لا يوجد وصف' }}
    </p>

    <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-4">
        <x-admin.moderation-actions
            :record="$company"
            type="company"
            :confirm-reject="$linkedRatings > 0 ? 'لدى هذه الجهة '.$linkedRatings.' تقييم؛ برفضها ستُخفى هي وتقييماتها من الموقع. يمكنك أولاً نقل التقييمات إلى جهة أخرى من بطاقة كل تقييم.' : null"
        />
        <span class="inline-flex items-center gap-1 text-xs {{ $linkedRatings > 0 ? 'font-medium text-amber-600' : 'text-slate-400' }}" title="عدد التقييمات المرتبطة بهذه الجهة">
            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.196-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.783-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
            {{ $linkedRatings }} تقييم
        </span>
    </div>
</article>
