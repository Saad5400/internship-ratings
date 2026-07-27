@props([
    'rating',
    'contactRevealed' => false,
    'voted' => false,
    'votable' => true,
])

@php
    $modalityLabel = $rating->modality?->label() ?? $rating->modality;
    $recLabel      = $rating->recommendation?->label() ?? (string) $rating->recommendation;
    $recClass      = match ($rating->recommendation?->value ?? $rating->recommendation) {
        'yes'   => 'bg-emerald-50 text-emerald-700 ring-emerald-600/15 dark:bg-emerald-950/40 dark:text-emerald-300 dark:ring-emerald-500/20',
        'maybe' => 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-950/40 dark:text-amber-300 dark:ring-amber-500/20',
        'no'    => 'bg-rose-50 text-rose-600 ring-rose-600/15 dark:bg-rose-950/40 dark:text-rose-300 dark:ring-rose-500/20',
        default => 'bg-slate-50 text-slate-600 ring-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:ring-slate-700',
    };
    $scoreBars = [
        ['label' => 'التعلّم', 'value' => $rating->rating_learning],
        ['label' => 'الإرشاد', 'value' => $rating->rating_mentorship],
        ['label' => 'العمل الحقيقي', 'value' => $rating->rating_real_work],
        ['label' => 'بيئة الفريق', 'value' => $rating->rating_team_environment],
        ['label' => 'التنظيم', 'value' => $rating->rating_organization],
    ];
    $degreeLabel = $rating->reviewer_degree?->label() ?? $rating->reviewer_degree;
    $academicParts = array_filter([
        $rating->reviewer_university,
        $rating->reviewer_college,
        $rating->reviewer_major,
        $degreeLabel,
    ]);
    $hasContact = $rating->willing_to_help === true && filled($rating->contact_method);
    $helpfulVotesCount = $rating->helpful_votes_count ?? 0;
@endphp

<article {{ $attributes->merge(['class' => 'rounded-xl border border-slate-200/80 bg-white p-6 shadow-xs transition-shadow hover:shadow-sm dark:border-slate-800 dark:bg-slate-900']) }}>
    {{-- Header: title + overall score --}}
    <div class="flex items-start justify-between gap-4">
        <div class="min-w-0 flex-1">
            <h4 class="font-semibold text-slate-900 dark:text-slate-100">{{ $rating->role_title }}</h4>

            {{-- Primary meta row --}}
            <div class="mt-1.5 flex flex-wrap items-center gap-1.5 text-xs">
                @if($rating->department)
                    <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-400">{{ $rating->department }}</span>
                @endif
                @if($rating->city)
                    <span class="inline-flex items-center gap-1 rounded-md bg-slate-100 px-2 py-0.5 font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                        <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        {{ $rating->city }}
                    </span>
                @endif
                <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                    <x-public.count-noun :count="$rating->duration_months" noun="months" :animate="false" />
                </span>
                @if($rating->modality)
                    <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-0.5 font-medium text-blue-700 dark:bg-blue-950/40 dark:text-blue-300">{{ $modalityLabel }}</span>
                @endif
                @if($rating->company?->type)
                    <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-400">{{ $rating->company->type->label() }}</span>
                @endif
            </div>
        </div>
        <x-public.overall-score :value="$rating->overall_rating" compact />
    </div>

    {{-- Facts row: stipend + yes/no chips --}}
    <div class="mt-4 flex flex-wrap items-center gap-2 text-xs">
        @if($rating->stipend_sar)
            <span class="inline-flex items-center gap-1 rounded-full bg-sky-50 px-2.5 py-1 font-semibold text-sky-700 ring-1 ring-inset ring-sky-600/20 dark:bg-sky-950/40 dark:text-sky-300 dark:ring-sky-500/20">
                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <x-public.count-up :value="$rating->stipend_sar" :duration="700" class="tabular-nums" /> ر.س / شهر
            </span>
        @else
            <span class="inline-flex items-center rounded-full bg-slate-50 px-2.5 py-1 font-medium text-slate-500 ring-1 ring-inset ring-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:ring-slate-700">غير مدفوع</span>
        @endif

        @if($rating->had_supervisor !== null)
            <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 font-medium ring-1 ring-inset {{ $rating->had_supervisor ? 'bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-950/40 dark:text-blue-300 dark:ring-blue-500/20' : 'bg-slate-50 text-slate-500 ring-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:ring-slate-700' }}">
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
            <span class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2.5 py-1 font-medium text-blue-700 ring-1 ring-inset ring-blue-600/20 dark:bg-blue-950/40 dark:text-blue-300 dark:ring-blue-500/20">
                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
                {{ $rating->mixed_env ? 'بيئة مختلطة' : 'بيئة غير مختلطة' }}
            </span>
        @endif

        @if($rating->job_offer !== null)
            <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 font-medium ring-1 ring-inset {{ $rating->job_offer ? 'bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-950/40 dark:text-blue-300 dark:ring-blue-500/20' : 'bg-slate-50 text-slate-500 ring-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:ring-slate-700' }}">
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
        @foreach($scoreBars as $index => $scoreBar)
            @if($scoreBar['value'] !== null)
                <x-public.rating-bar
                    :label="$scoreBar['label']"
                    :value="$scoreBar['value']"
                    :delay="$index * 80"
                />
            @endif
        @endforeach
    </div>

    {{-- Review text --}}
    <p class="mt-5 text-slate-600 leading-relaxed whitespace-pre-line dark:text-slate-400">{{ $rating->review_text }}</p>

    {{-- Pros / cons --}}
    @if($rating->pros || $rating->cons)
        <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
            @if($rating->pros)
                <div class="rounded-lg rounded-s-none border-s-2 border-s-sky-500 bg-sky-50/60 px-3 py-2 dark:border-s-sky-500/60 dark:bg-sky-950/30">
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-sky-700 dark:text-sky-300">المزايا</div>
                    <div class="mt-0.5 text-sm text-slate-700 dark:text-slate-300">{{ $rating->pros }}</div>
                </div>
            @endif
            @if($rating->cons)
                <div class="rounded-lg rounded-s-none border-s-2 border-s-slate-400 bg-slate-100/60 px-3 py-2 dark:border-s-slate-600 dark:bg-slate-800/60">
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-400">العيوب</div>
                    <div class="mt-0.5 text-sm text-slate-700 dark:text-slate-300">{{ $rating->cons }}</div>
                </div>
            @endif
        </div>
    @endif

    {{-- Academic background + application method --}}
    @if(! empty($academicParts) || $rating->application_method)
        <dl class="mt-4 grid grid-cols-1 gap-3 text-xs sm:grid-cols-2">
            @if(! empty($academicParts))
                <div class="rounded-lg bg-slate-50/70 px-3 py-2 ring-1 ring-inset ring-slate-200 dark:bg-slate-800/50 dark:ring-slate-700">
                    <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">الخلفية الأكاديمية</dt>
                    <dd class="mt-0.5 text-sm text-slate-700 dark:text-slate-300">{{ implode(' — ', $academicParts) }}</dd>
                </div>
            @endif
            @if($rating->application_method)
                <div class="rounded-lg bg-slate-50/70 px-3 py-2 ring-1 ring-inset ring-slate-200 dark:bg-slate-800/50 dark:ring-slate-700">
                    <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">طريقة التقديم</dt>
                    <dd class="mt-0.5 text-sm text-slate-700 dark:text-slate-300">{{ $rating->application_method }}</dd>
                </div>
            @endif
        </dl>
    @endif

    {{-- Willing to help + click-to-reveal contact (hidden from crawlers) --}}
    @if($hasContact)
        <div class="mt-4 rounded-lg border border-sky-200/70 bg-sky-50/60 px-3 py-2.5 dark:border-sky-800/60 dark:bg-sky-950/30">
            <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
                <span class="inline-flex items-center gap-1.5 font-medium text-sky-800 dark:text-sky-300">
                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                    مستعد لمساعدة الآخرين
                </span>
                @if($contactRevealed)
                    <span class="inline-flex min-w-0 items-center gap-1.5 rounded-md bg-white px-2 py-1 font-medium text-sky-900 ring-1 ring-inset ring-sky-300 break-all dark:bg-slate-900 dark:text-sky-200 dark:ring-sky-700">
                        <svg class="size-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        {{ $rating->contact_method }}
                    </span>
                @else
                    <button type="button"
                        wire:click="revealContact({{ $rating->id }})"
                        wire:loading.attr="disabled"
                        wire:target="revealContact({{ $rating->id }})"
                        class="inline-flex items-center gap-1.5 rounded-md bg-white px-2.5 py-1 text-[11px] font-semibold text-sky-700 shadow-xs ring-1 ring-inset ring-sky-300 transition-all hover:bg-sky-100 active:scale-[0.98] disabled:cursor-wait disabled:opacity-60 dark:bg-slate-900 dark:text-sky-300 dark:ring-sky-700 dark:hover:bg-slate-800">
                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        إظهار طريقة التواصل
                    </button>
                @endif
            </div>
        </div>
    @endif

    {{-- Footer: recommendation + author + date --}}
    <div class="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-4 text-xs dark:border-slate-800">
        <span class="inline-flex items-center rounded-full px-2.5 py-1 font-semibold ring-1 ring-inset {{ $recClass }}">{{ $recLabel }}</span>
        <div class="flex items-center gap-3 text-slate-400 dark:text-slate-500">
            @if($votable)
            <button type="button"
                wire:click="voteHelpful({{ $rating->id }})"
                wire:loading.attr="disabled"
                wire:target="voteHelpful({{ $rating->id }})"
                aria-pressed="{{ $voted ? 'true' : 'false' }}"
                aria-label="{{ $voted ? 'إزالة علامة أن هذا التقييم مفيد' : 'ضع علامة أن هذا التقييم مفيد' }}"
                class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 font-semibold ring-1 ring-inset transition-all active:scale-[0.98] disabled:cursor-wait {{ $voted ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-300 dark:ring-emerald-500/20 dark:hover:bg-emerald-950/60' : 'bg-slate-50 text-slate-500 ring-slate-200 hover:bg-slate-100 dark:bg-slate-800 dark:text-slate-400 dark:ring-slate-700 dark:hover:bg-slate-700' }}">
                @if($voted)
                    <svg class="size-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M1 8.25a1.25 1.25 0 1 1 2.5 0v7.5a1.25 1.25 0 1 1-2.5 0v-7.5ZM11 3V1.7c0-.268.14-.526.395-.607A2 2 0 0 1 14 3c0 .995-.182 1.948-.514 2.826-.204.54.166 1.174.744 1.174h2.52c1.243 0 2.261.988 2.146 2.161a45.435 45.435 0 0 1-.372 3.045c-.164 1.166-1.188 2.1-2.365 2.1h-4.85c-1.056 0-2.132-.16-3.129-.492a10 10 0 0 0-1.65-.412c-.42-.08-.83-.13-1.23-.13h-.5a.75.75 0 0 1-.75-.75V7.9c0-.34.135-.667.377-.909A6.964 6.964 0 0 0 8.5 3c0-.42.16-.826.446-1.113L11 3Z" />
                    </svg>
                @else
                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.633 10.5c.806 0 1.533-.446 2.031-1.08a9.041 9.041 0 012.861-2.4c.723-.384 1.35-.956 1.653-1.715a4.498 4.498 0 00.322-1.672V3a.75.75 0 01.75-.75A2.25 2.25 0 0116.5 4.5c0 1.152-.26 2.243-.723 3.218-.266.558.107 1.282.725 1.282h3.126c1.026 0 1.945.694 2.054 1.715.045.422.068.85.068 1.285a11.95 11.95 0 01-2.649 7.521c-.388.482-1.006.729-1.622.729H13.48c-.483 0-.964-.078-1.423-.23l-3.114-1.04a4.501 4.501 0 00-1.423-.23H5.904M14.25 9h2.25M5.904 18.75c.083.205.173.405.27.602.197.4-.078.898-.523.898h-.908c-.889 0-1.713-.518-1.972-1.368a12 12 0 01-.521-3.507c0-1.553.295-3.036.831-4.398C3.387 10.203 4.167 9.75 5 9.75h1.053c.472 0 .745.556.5.96a8.958 8.958 0 00-1.302 4.665c0 1.194.232 2.333.654 3.375z"/></svg>
                @endif
                مفيد
                @if($helpfulVotesCount > 0)
                    <span class="tabular-nums">{{ $helpfulVotesCount }}</span>
                @endif
            </button>
            @elseif($helpfulVotesCount > 0)
                <span class="inline-flex items-center gap-1 text-xs text-slate-400 dark:text-slate-500" title="عدد من وجدوه مفيداً">
                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/></svg>
                    <span class="tabular-nums">{{ $helpfulVotesCount }}</span> مفيد
                </span>
            @endif
            <span class="inline-flex items-center gap-1.5">
                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                {{ $rating->reviewer_name ?? 'مجهول' }}@if($rating->reviewer_major) <span class="text-slate-400 dark:text-slate-500"> — {{ $rating->reviewer_major }}</span>@endif
            </span>
            <time datetime="{{ $rating->created_at->toDateString() }}">{{ $rating->created_at->diffForHumans() }}</time>
        </div>
    </div>
</article>
