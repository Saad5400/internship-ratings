@props(['company'])

<a href="{{ route('companies.show', $company) }}" wire:navigate
    class="group block rounded-xl border border-slate-200/80 bg-white p-6 shadow-xs transition-all duration-200 hover:border-blue-200 hover:shadow-md motion-safe:hover:-translate-y-0.5">
    <div class="flex items-start justify-between gap-4">
        <div class="min-w-0 flex-1">
            <h3 class="text-lg font-semibold text-slate-900 group-hover:text-blue-600 transition-colors truncate">{{ $company->name }}</h3>
            @if($company->description)
                <p class="mt-1 text-sm text-slate-500 line-clamp-2 leading-relaxed">{{ $company->description }}</p>
            @endif
        </div>
        @if($company->average_rating)
            <x-public.overall-score :value="$company->average_rating" />
        @endif
    </div>

    <div class="mt-5 flex items-center gap-3 text-xs text-slate-400">
        <span class="inline-flex items-center gap-1.5">
            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
            <x-public.count-up :value="$company->ratings_count" :duration="650" class="tabular-nums" />
            {{ $company->ratings_count === 1 ? 'تقييم' : 'تقييمات' }}
        </span>
        @if($company->website)
            <span class="size-1 rounded-full bg-slate-300"></span>
            <span class="truncate">{{ parse_url($company->website, PHP_URL_HOST) }}</span>
        @endif
    </div>
</a>
