@props(['company', 'hit' => null])

@php
    $latestRating = $company->relationLoaded('latestApprovedRating') ? $company->latestApprovedRating : null;
    $quote = $latestRating && filled($latestRating->review_text) ? $latestRating->review_text : null;

    // While searching, say what the card matched on. A result found by meaning
    // rather than by literal text has no visible overlap with the query, so
    // without this it reads as a bug (docs/ux-principles.md §4).
    $matchedFields = $hit?->matchedFields() ?? [];
@endphp

<a href="{{ route('companies.show', $company) }}" wire:navigate
    class="group block rounded-xl border border-slate-200/80 bg-white p-6 shadow-xs transition-all duration-200 hover:border-blue-200 hover:shadow-md motion-safe:hover:-translate-y-0.5 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-blue-900">
    <div class="flex items-start gap-4">
        <x-public.company-avatar :company="$company" />

        <div class="min-w-0 flex-1">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0 flex-1">
                    <h3 class="text-lg font-semibold text-slate-900 group-hover:text-blue-600 transition-colors truncate dark:text-slate-100">{{ $company->name }}</h3>
                    @if($company->description)
                        <p class="mt-1 text-sm text-slate-500 line-clamp-2 leading-relaxed dark:text-slate-400">{{ $company->description }}</p>
                    @endif
                </div>
                @if($company->average_rating)
                    <x-public.overall-score :value="$company->average_rating" />
                @endif
            </div>

            @if($quote)
                <p class="mt-3 line-clamp-1 text-sm italic text-slate-500 dark:text-slate-400">&rdquo;{{ $quote }}&ldquo;</p>
            @endif

            @if($matchedFields !== [])
                <div class="mt-3 flex flex-wrap items-center gap-1.5 text-xs">
                    <span class="text-slate-400 dark:text-slate-500">
                        {{ $hit->isSemanticMatch() ? 'مطابقة بالمعنى:' : 'طابق:' }}
                    </span>
                    @foreach($matchedFields as $field)
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 font-medium text-slate-500 dark:bg-slate-800 dark:text-slate-400">{{ $field->label() }}</span>
                    @endforeach
                </div>
            @endif

            <div class="mt-3 flex items-center gap-3 text-xs text-slate-400 dark:text-slate-500">
                @if($company->ratings_count === 0)
                    <span class="inline-flex items-center gap-1.5 font-medium text-blue-500">
                        كن أول من يقيّم
                        <svg class="size-3.5 rotate-180 motion-safe:transition-transform motion-safe:group-hover:-translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14 19l7-7-7-7m7 7H3"/></svg>
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5">
                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                        <x-public.count-up :value="$company->ratings_count" :duration="650" class="tabular-nums" />
                        {{ $company->ratings_count === 1 ? 'تقييم' : 'تقييمات' }}
                    </span>
                @endif
                @if($latestRating)
                    <span class="size-1 rounded-full bg-slate-300 dark:bg-slate-700"></span>
                    <span class="truncate">آخر تقييم {{ $latestRating->created_at->diffForHumans() }}</span>
                @elseif($company->website)
                    <span class="size-1 rounded-full bg-slate-300 dark:bg-slate-700"></span>
                    <span class="truncate">{{ parse_url($company->website, PHP_URL_HOST) }}</span>
                @endif
            </div>
        </div>
    </div>
</a>
