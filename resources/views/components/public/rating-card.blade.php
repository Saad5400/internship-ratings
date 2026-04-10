@props(['rating'])

@php
    $modalityLabels = ['onsite' => 'حضوري', 'hybrid' => 'هجين', 'remote' => 'عن بُعد'];
    $sectorLabels = ['government' => 'حكومي', 'private' => 'خاص', 'nonprofit' => 'غير ربحي', 'other' => 'أخرى'];
    $recLabels = [
        'yes' => ['أنصح به', 'bg-green-50 text-green-700 ring-green-600/20'],
        'maybe' => ['توصية مشروطة', 'bg-amber-50 text-amber-700 ring-amber-600/20'],
        'no' => ['لا أنصح', 'bg-red-50 text-red-700 ring-red-600/20'],
    ];
    [$recLabel, $recClass] = $recLabels[$rating->recommendation] ?? [$rating->recommendation, 'bg-slate-50 text-slate-600'];
@endphp

<article class="rounded-xl border border-slate-200/80 bg-white p-6 shadow-xs transition-shadow hover:shadow-sm">
    {{-- Header: title + overall score --}}
    <div class="flex items-start justify-between gap-4">
        <div class="min-w-0 flex-1">
            <h4 class="font-semibold text-slate-900">{{ $rating->role_title }}</h4>

            {{-- Primary meta row --}}
            <div class="mt-1.5 flex flex-wrap items-center gap-1.5 text-xs">
                @if($rating->department)
                    <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 font-medium text-slate-600">{{ $rating->department }}</span>
                @endif
                @if($rating->city)
                    <span class="inline-flex items-center gap-1 rounded-md bg-slate-100 px-2 py-0.5 font-medium text-slate-600">
                        <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        {{ $rating->city }}
                    </span>
                @endif
                <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 font-medium text-slate-600">
                    {{ $rating->duration_months }} {{ $rating->duration_months === 1 ? 'شهر' : 'أشهر' }}
                </span>
                @if($rating->modality)
                    <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-0.5 font-medium text-blue-700">{{ $modalityLabels[$rating->modality] ?? $rating->modality }}</span>
                @endif
                @if($rating->sector)
                    <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 font-medium text-slate-600">{{ $sectorLabels[$rating->sector] ?? $rating->sector }}</span>
                @endif
            </div>
        </div>
        <div class="shrink-0 flex items-baseline gap-0.5">
            <x-public.count-up :value="$rating->overall_rating" :decimals="0" :duration="700" class="text-3xl font-bold text-slate-900 tabular-nums" />
            <span class="text-sm text-slate-400 font-normal">/5</span>
        </div>
    </div>

    {{-- Facts row: stipend + yes/no chips --}}
    <div class="mt-4 flex flex-wrap items-center gap-2 text-xs">
        @if($rating->stipend_sar)
            <span class="inline-flex items-center gap-1 rounded-full bg-green-50 px-2.5 py-1 font-semibold text-green-700 ring-1 ring-inset ring-green-600/20">
                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <x-public.count-up :value="$rating->stipend_sar" :duration="700" class="tabular-nums" /> ر.س / شهر
            </span>
        @else
            <span class="inline-flex items-center rounded-full bg-slate-50 px-2.5 py-1 font-medium text-slate-500 ring-1 ring-inset ring-slate-200">غير مدفوع</span>
        @endif

        @if($rating->had_supervisor !== null)
            <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 font-medium ring-1 ring-inset {{ $rating->had_supervisor ? 'bg-blue-50 text-blue-700 ring-blue-600/20' : 'bg-slate-50 text-slate-500 ring-slate-200' }}">
                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    @if($rating->had_supervisor)
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    @else
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    @endif
                </svg>
                مرشد مباشر
            </span>
        @endif

        @if($rating->mixed_env !== null)
            <span class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2.5 py-1 font-medium text-blue-700 ring-1 ring-inset ring-blue-600/20">
                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
                {{ $rating->mixed_env ? 'بيئة مختلطة' : 'بيئة غير مختلطة' }}
            </span>
        @endif

        @if($rating->job_offer !== null)
            <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 font-medium ring-1 ring-inset {{ $rating->job_offer ? 'bg-blue-50 text-blue-700 ring-blue-600/20' : 'bg-slate-50 text-slate-500 ring-slate-200' }}">
                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    @if($rating->job_offer)
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    @else
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    @endif
                </svg>
                انتهى بعرض عمل
            </span>
        @endif
    </div>

    {{-- Multi-criteria bars --}}
    <div class="mt-5 space-y-2.5">
        <x-public.rating-bar label="الإرشاد" :value="$rating->rating_mentorship" :delay="0" />
        <x-public.rating-bar label="التعلّم" :value="$rating->rating_learning" :delay="80" />
        <x-public.rating-bar label="بيئة العمل" :value="$rating->rating_culture" :delay="160" />
        <x-public.rating-bar label="المكافأة" :value="$rating->rating_compensation" :delay="240" />
    </div>

    {{-- Review text --}}
    <p class="mt-5 text-slate-600 leading-relaxed whitespace-pre-line">{{ $rating->review_text }}</p>

    {{-- Pros / cons --}}
    @if($rating->pros || $rating->cons)
        <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
            @if($rating->pros)
                <div class="rounded-lg rounded-s-none border-s-2 border-s-green-500 bg-green-50/50 px-3 py-2">
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-green-700">المزايا</div>
                    <div class="mt-0.5 text-sm text-slate-700">{{ $rating->pros }}</div>
                </div>
            @endif
            @if($rating->cons)
                <div class="rounded-lg rounded-s-none border-s-2 border-s-red-500 bg-red-50/50 px-3 py-2">
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-red-700">العيوب</div>
                    <div class="mt-0.5 text-sm text-slate-700">{{ $rating->cons }}</div>
                </div>
            @endif
        </div>
    @endif

    {{-- Footer: recommendation + author + date --}}
    <div class="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-4 text-xs">
        <span class="inline-flex items-center rounded-full px-2.5 py-1 font-semibold ring-1 ring-inset {{ $recClass }}">{{ $recLabel }}</span>
        <div class="flex items-center gap-3 text-slate-400">
            <span class="inline-flex items-center gap-1.5">
                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                {{ $rating->reviewer_name ?? 'مجهول' }}@if($rating->reviewer_major) <span class="text-slate-400"> — {{ $rating->reviewer_major }}</span>@endif
            </span>
            <time datetime="{{ $rating->created_at->toDateString() }}">{{ $rating->created_at->diffForHumans() }}</time>
        </div>
    </div>
</article>
