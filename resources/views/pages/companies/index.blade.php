<?php

use App\Models\Company;
use App\Models\Rating;
use App\Support\CompanyFacets;
use App\Support\Search\CompanySearch;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts.public')] #[Title('الجهات')] class extends Component {
    #[Url(as: 'search', except: '')]
    public string $search = '';

    #[Url(as: 'sort', except: 'highest_rated')]
    public string $sort = 'highest_rated';

    /**
     * Selected facet values keyed by facet, e.g. `['city' => ['الرياض']]`.
     * {@see \App\Support\CompanyFacets} owns which keys and values are legal.
     *
     * @var array<string, list<string>>
     */
    #[Url(as: 'f', except: [])]
    public array $filters = [];

    /** How many facets are shown before the "more filters" toggle. */
    protected int $primaryFacetCount = 3;

    public int $perPage = 12;

    protected int $pageSize = 12;

    /**
     * Relevance is only offered while there is a query to be relevant to —
     * an empty search has no ranking to sort by.
     *
     * @return array<int, array{id: string, name: string}>
     */
    public function getSortOptionsProperty(): array
    {
        return array_values(array_filter([
            $this->search !== '' ? ['id' => 'relevance', 'name' => 'الأكثر صلة'] : null,
            ['id' => 'highest_rated', 'name' => 'الأعلى تقييماً'],
            ['id' => 'most_rated', 'name' => 'الأكثر تقييماً'],
            ['id' => 'most_recently_rated', 'name' => 'الأحدث تقييماً'],
        ]));
    }

    /** @return list<string> */
    protected function allowedSorts(): array
    {
        return ['relevance', 'highest_rated', 'most_rated', 'most_recently_rated'];
    }

    public function mount(): void
    {
        $this->filters = CompanyFacets::sanitize($this->filters);
    }

    public function rendering($view): void
    {
        $view->with('metaDescription', 'تصفّح تقييمات جهات التدريب التعاوني والصيفي من المتدربين أنفسهم. قارن بين الشركات والجهات حسب تقييمات المتدربين، واطّلع على التجارب الحقيقية قبل اختيار جهة تدريبك.');
    }

    public function updatingSearch(): void
    {
        $this->perPage = $this->pageSize;
        $this->resetPage();
    }

    public function updatingSort(): void
    {
        $this->perPage = $this->pageSize;
        $this->resetPage();
    }

    public function updatedSort(mixed $value): void
    {
        if (! in_array($value, $this->allowedSorts(), true)) {
            $this->sort = 'highest_rated';
        }
    }

    /**
     * Typing a query switches the list to relevance order, and clearing it
     * switches back — but only from the default sort. Someone who deliberately
     * chose "الأكثر تقييماً" keeps it while they search.
     */
    public function updatedSearch(): void
    {
        if ($this->search !== '' && $this->sort === 'highest_rated') {
            $this->sort = 'relevance';
        }

        if ($this->search === '' && $this->sort === 'relevance') {
            $this->sort = 'highest_rated';
        }
    }

    /** Add or remove one value from a facet — filters apply instantly. */
    public function toggleFilter(string $facet, string $value): void
    {
        if (! in_array($facet, CompanyFacets::keys(), true)) {
            return;
        }

        $selected = $this->filters[$facet] ?? [];

        $selected = in_array($value, $selected, true)
            ? array_values(array_diff($selected, [$value]))
            : [...$selected, $value];

        if ($selected === []) {
            unset($this->filters[$facet]);
        } else {
            $this->filters[$facet] = $selected;
        }

        $this->perPage = $this->pageSize;
        $this->resetPage();
    }

    public function clearFacet(string $facet): void
    {
        unset($this->filters[$facet]);

        $this->perPage = $this->pageSize;
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->filters = [];
        $this->search = '';

        $this->perPage = $this->pageSize;
        $this->resetPage();
    }

    public function loadMore(): void
    {
        if (! $this->hasMore) {
            return;
        }

        $this->perPage += $this->pageSize;
        unset($this->companyResults, $this->companies, $this->hasMore);
    }

    protected function resetPage(): void
    {
        unset(
            $this->companyResults,
            $this->companies,
            $this->searchHits,
            $this->hasMore,
            $this->facetOptions,
            $this->facets,
            $this->activeFilterCount,
            $this->hasHiddenFacets,
            $this->isNarrowed,
        );
    }

    #[Computed]
    public function companyResults()
    {
        $query = Company::approved()
            ->withCount(['ratings as ratings_count' => fn ($q) => $q->where('status', 'approved')])
            ->withAvg('approvedRatings as average_rating', 'overall_rating')
            ->with('latestApprovedRating')
            ->matchingSearch($this->search);

        CompanyFacets::apply($query, $this->filters);

        match ($this->sort) {
            'relevance' => $query->orderByRelevance($this->search),
            'most_rated' => $query->orderByDesc('ratings_count'),
            'most_recently_rated' => $query->orderByRaw(
                "(select max(created_at) from ratings where ratings.company_id = companies.id and status = 'approved') desc nulls last"
            ),
            default => $query->orderByRaw(
                "(select avg(overall_rating) from ratings where ratings.company_id = companies.id and status = 'approved') desc nulls last"
            ),
        };

        return $query
            ->orderByDesc('ratings_count')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->take($this->perPage + 1)
            ->get();
    }

    #[Computed]
    public function companies()
    {
        return $this->companyResults->take($this->perPage);
    }

    /**
     * The per-field score breakdown behind each result, keyed by company id, so
     * a card can say what it matched on. Free to read: the ranker memoizes this
     * query for the request, and the results above already paid for it.
     *
     * @return array<int, \App\Support\Search\SearchHit>
     */
    #[Computed]
    public function searchHits(): array
    {
        return $this->search === '' ? [] : app(CompanySearch::class)->search($this->search)->all();
    }

    #[Computed]
    public function hasMore(): bool
    {
        return $this->companyResults->count() > $this->perPage;
    }

    /**
     * @return array<string, list<array{value: string, label: string, count: int}>>
     */
    #[Computed]
    public function facetOptions(): array
    {
        return CompanyFacets::options($this->search, $this->filters);
    }

    /**
     * The facets worth putting on screen. A facet with fewer than two options
     * offers no real choice, so it's dropped — but one that's already filtering
     * always keeps its chip so the user can undo it. The first few are shown
     * up front; the rest hide behind "مزيد من الفلاتر".
     *
     * @return list<array{key: string, label: string, options: list<array{value: string, label: string, count: int}>, selected: list<string>, searchable: bool, primary: bool}>
     */
    #[Computed]
    public function facets(): array
    {
        $definitions = CompanyFacets::definitions();
        $facets = [];
        $shown = 0;

        foreach ($definitions as $key => $definition) {
            $options = $this->facetOptions[$key] ?? [];
            $selected = $this->filters[$key] ?? [];

            if (count($options) < 2 && $selected === []) {
                continue;
            }

            $facets[] = [
                'key' => $key,
                'label' => $definition['label'],
                'options' => $options,
                'selected' => $selected,
                'searchable' => $definition['searchable'],
                // An active facet is never buried behind the toggle.
                'primary' => $shown < $this->primaryFacetCount || $selected !== [],
            ];

            $shown++;
        }

        return $facets;
    }

    #[Computed]
    public function activeFilterCount(): int
    {
        return array_sum(array_map('count', $this->filters));
    }

    /** Whether the user has narrowed the list, which changes the empty state. */
    #[Computed]
    public function isNarrowed(): bool
    {
        return $this->search !== '' || $this->activeFilterCount > 0;
    }

    #[Computed]
    public function hasHiddenFacets(): bool
    {
        return collect($this->facets)->contains(fn (array $facet): bool => ! $facet['primary']);
    }

    /**
     * @return array{ratings: int, companies: int}
     */
    #[Computed]
    public function stats(): array
    {
        return Cache::remember('public-stats', 300, fn (): array => [
            'ratings' => Rating::approved()->count(),
            'companies' => Company::approved()->count(),
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Rating>
     */
    #[Computed]
    public function latestReviews()
    {
        // Only the IDs are cached. Caching the models themselves serializes an
        // Eloquent collection into the store, and every later request dies
        // unserializing it ("method on an incomplete object").
        $ids = Cache::remember('public-latest-review-ids', 300, fn (): array => Rating::approved()
            ->whereNotNull('review_text')
            ->where('review_text', '!=', '')
            ->whereHas('company', fn ($q) => $q->approved())
            ->latest('created_at')
            ->take(3)
            ->pluck('id')
            ->all());

        return Rating::whereKey($ids)
            ->with('company')
            ->latest('created_at')
            ->get();
    }
}; ?>

<div class="space-y-8">
    {{-- Hero --}}
    <div class="rounded-2xl border border-slate-200 bg-gradient-to-b from-blue-50/40 to-white px-6 py-10 text-center sm:px-10 sm:py-14 dark:border-slate-800 dark:from-blue-950/30 dark:to-slate-900">
        <h1 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl dark:text-slate-100">تجارب تدريب حقيقية، من متدربين مثلك</h1>
        <p class="mx-auto mt-3 max-w-xl text-slate-500 dark:text-slate-400">اقرأ تقييمات صادقة عن جهات التدريب التعاوني والصيفي قبل أن تختار جهتك.</p>

        <div class="mt-6 flex items-center justify-center gap-3 text-sm text-slate-500 dark:text-slate-400">
            <span class="inline-flex items-baseline gap-1.5">
                <x-public.count-up :value="$this->stats['ratings']" :duration="900" class="text-lg font-bold tabular-nums text-slate-900 dark:text-slate-100" />
                <span>تقييم</span>
            </span>
            <span class="size-1 rounded-full bg-slate-300 dark:bg-slate-700" aria-hidden="true"></span>
            <span class="inline-flex items-baseline gap-1.5">
                <x-public.count-up :value="$this->stats['companies']" :duration="900" class="text-lg font-bold tabular-nums text-slate-900 dark:text-slate-100" />
                <span>جهة</span>
            </span>
        </div>
    </div>

    {{-- Debounced live search + sort, single row on all viewports --}}
    <div class="flex flex-row items-stretch gap-3">
        <div class="relative min-w-0 flex-1">
            <span class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-4 text-slate-400 dark:text-slate-500">
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </span>
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="ابحث عن جهة..."
                class="w-full rounded-xl border border-slate-200 bg-white ps-11 pe-11 py-3 text-sm text-slate-900 placeholder-slate-400 shadow-xs transition-all focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100 dark:placeholder-slate-500"
                aria-label="ابحث عن جهة"
            />

            {{-- Clear button --}}
            @if($search !== '')
                <button
                    type="button"
                    wire:click="$set('search', '')"
                    class="absolute inset-y-0 end-0 flex items-center pe-4 text-slate-400 transition-colors hover:text-slate-600 dark:text-slate-500 dark:hover:text-slate-300"
                    aria-label="مسح البحث"
                >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            @endif

            {{-- Inline spinner while the search request is in flight --}}
            <div wire:loading wire:target="search" class="absolute inset-y-0 end-0 flex items-center pe-4 text-slate-400 dark:text-slate-500">
                <svg class="size-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
            </div>
        </div>

        <div class="relative size-[46px] shrink-0 compact-select-trigger" wire:key="sort-select-wrapper" title="ترتيب حسب">
            <span class="pointer-events-none absolute inset-0 z-10 flex items-center justify-center text-slate-500 dark:text-slate-400">
                <svg class="size-5 compact-select-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 6h13M3 12h9M3 18h5M17 4v16m0 0l-3-3m3 3l3-3"/>
                </svg>
            </span>
            <x-public.nice-select
                name="sort"
                wire:model.live="sort"
                :options="$this->sortOptions"
                aria-label="ترتيب حسب"
                offline
                :clearable="false"
                class="!size-[46px] !min-h-[46px] rounded-xl"
            >
                @scope('item', $option)
                    <div class="p-3 border-s-4 border-s-transparent hover:bg-slate-50 dark:hover:bg-slate-800">
                        <div class="font-medium text-slate-900 dark:text-slate-100">{{ data_get($option, 'name') }}</div>
                    </div>
                @endscope
            </x-public.nice-select>
        </div>
    </div>

    {{-- Faceted filters: values OR inside a chip, chips AND together. Options
         that would return nothing are never offered, so every click lands. --}}
    @if($this->facets !== [])
        <div class="flex flex-wrap items-center gap-2" x-data="{ showAll: false }">
            @foreach($this->facets as $facet)
                <div
                    wire:key="facet-{{ $facet['key'] }}"
                    @unless($facet['primary']) x-show="showAll" x-cloak @endunless
                >
                    <x-public.filter-chip
                        :facet="$facet['key']"
                        :label="$facet['label']"
                        :options="$facet['options']"
                        :selected="$facet['selected']"
                        :searchable="$facet['searchable']"
                    />
                </div>
            @endforeach

            @if($this->hasHiddenFacets)
                <button
                    type="button"
                    @click="showAll = ! showAll"
                    class="rounded-full px-3 py-2 text-sm font-medium text-slate-500 transition-colors hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100"
                >
                    <span x-show="! showAll">مزيد من الفلاتر</span>
                    <span x-show="showAll" x-cloak>فلاتر أقل</span>
                </button>
            @endif

            @if($this->activeFilterCount > 0)
                <button
                    type="button"
                    wire:click="clearFilters"
                    class="inline-flex items-center gap-1.5 rounded-full px-3 py-2 text-sm font-medium text-blue-600 transition-colors hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
                >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    مسح الفلاتر
                </button>
            @endif
        </div>
    @endif

    {{-- "Latest reviews" activity strip --}}
    @if($this->latestReviews->isNotEmpty())
        <div class="space-y-3">
            <div class="flex items-center gap-2">
                <span class="size-2 rounded-full bg-emerald-500 motion-safe:animate-pulse" aria-hidden="true"></span>
                <h2 class="text-sm font-semibold text-slate-500 dark:text-slate-400">أحدث التقييمات</h2>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                @foreach($this->latestReviews as $review)
                    <div wire:key="latest-review-{{ $review->id }}" class="rounded-xl border border-slate-200/80 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                        <p class="text-sm leading-relaxed text-slate-600 dark:text-slate-400">
                            <span class="text-lg font-bold leading-none text-blue-300 dark:text-blue-500" aria-hidden="true">&rdquo;</span>
                            {{ Str::limit($review->review_text, 110) }}
                        </p>
                        <div class="mt-3 flex items-center justify-between gap-2 text-xs text-slate-400 dark:text-slate-500">
                            <span class="min-w-0 truncate">
                                {{ $review->role_title }} ·
                                <a href="{{ route('companies.show', $review->company) }}" wire:navigate class="font-medium text-slate-500 hover:text-blue-600 transition-colors dark:text-slate-400 dark:hover:text-blue-400">{{ $review->company->name }}</a>
                            </span>
                            <span class="shrink-0">{{ $review->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if($this->companies->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-200 bg-white py-16 text-center dark:border-slate-800 dark:bg-slate-900">
            <svg class="mx-auto size-16 text-slate-300 dark:text-slate-700" fill="none" viewBox="0 0 64 64" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <rect x="10" y="24" width="14" height="30" rx="1.5"/>
                <rect x="26" y="14" width="16" height="40" rx="1.5"/>
                <path stroke-linecap="round" d="M15 31h4M15 38h4M15 45h4M31 21h6M31 28h6M31 35h6M31 42h6"/>
                <circle cx="47" cy="41" r="8"/>
                <path stroke-linecap="round" d="M53 47l6 6"/>
            </svg>
            <p class="mt-4 text-sm text-slate-500 dark:text-slate-400">لا توجد جهات {{ $this->isNarrowed ? 'تطابق اختيارك' : 'حالياً' }}</p>
            @if($this->isNarrowed)
                <button type="button" wire:click="clearFilters" class="mt-3 text-sm font-medium text-blue-500 transition-colors hover:text-blue-600">مسح البحث والفلاتر</button>
            @endif
        </div>
    @else
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            @foreach($this->companies as $company)
                <div wire:key="company-{{ $company->id }}">
                    <x-public.company-card :company="$company" :hit="$this->searchHits[$company->id] ?? null" />
                </div>
            @endforeach
        </div>

        {{-- Infinite scroll sentinel --}}
        @if($this->hasMore)
            <div
                wire:key="sentinel-{{ $perPage }}"
                x-intersect.once="$wire.loadMore()"
                wire:loading.remove
                wire:target="loadMore"
                class="h-10"
                aria-hidden="true"
            ></div>

            <div wire:loading.grid wire:target="loadMore" class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-public.company-card-skeleton :count="2" />
            </div>
        @endif
    @endif

    @php
        $itemListElements = $this->companies->values()->map(fn ($company, $index) => [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'name' => $company->name,
            'url' => route('companies.show', $company),
        ])->all();

        $jsonLd = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'WebSite',
                    '@id' => url('/').'#website',
                    'url' => url('/'),
                    'name' => 'تقييم التدريب',
                    'description' => 'منصّة عربية لتقييم جهات التدريب التعاوني والصيفي من المتدربين أنفسهم.',
                    'inLanguage' => 'ar',
                    'publisher' => ['@id' => url('/').'#organization'],
                ],
                [
                    '@type' => 'Organization',
                    '@id' => url('/').'#organization',
                    'name' => 'تقييم التدريب',
                    'url' => url('/'),
                    'logo' => url('/og-image.png'),
                ],
                [
                    '@type' => 'ItemList',
                    'name' => 'جهات التدريب',
                    'itemListElement' => $itemListElements,
                ],
            ],
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
</div>
