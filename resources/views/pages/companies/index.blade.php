<?php

use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Rating;
use App\Support\Arabic;
use App\Support\CompanyFacets;
use App\Support\PopularSearches;
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

    /**
     * Concrete queries offered when the search box is focused and empty.
     *
     * People type company names into a search box because that is what search
     * boxes have always wanted. So these deliberately suggest everything *but*
     * a company name: a city and a role, both pulled from real data so a
     * suggestion never lands on an empty page, and a concept no record contains
     * verbatim — the one that demonstrates meaning-matching.
     *
     * @return list<string>
     */
    #[Computed]
    public function searchExamples(): array
    {
        return Cache::remember('public-search-examples', 600, function (): array {
            $topOf = fn (string $column): ?string => Rating::approved()
                ->whereNotNull($column)
                ->where($column, '!=', '')
                ->groupBy($column)
                ->orderByRaw('count(*) desc')
                ->value($column);

            $city = $topOf('city');

            return array_values(array_filter([
                $city ? "تدريب في {$city}" : null,
                $topOf('role_title'),
                'شركات تقنية',
            ]));
        });
    }

    /**
     * The chips offered under an empty search box: the curated examples, then
     * what other people actually searched for.
     *
     * They render as one undifferentiated row on purpose. Which suggestions we
     * wrote and which the crowd supplied is our concern, not the visitor's, and
     * labelling the split would turn a hint into an advertisement for itself.
     *
     * @return list<string>
     */
    #[Computed]
    public function searchSuggestions(): array
    {
        $suggestions = [];

        foreach ([...$this->searchExamples, ...PopularSearches::top(4)] as $term) {
            // Keyed by the normalizer so a popular term never repeats a curated
            // one under a different spelling of the same word.
            $key = Arabic::normalize($term);

            if ($key === '' || isset($suggestions[$key])) {
                continue;
            }

            $suggestions[$key] = $term;
        }

        return array_slice(array_values($suggestions), 0, 6);
    }

    /** Run a suggested query. Taken by index so no Arabic string is ever quoted into markup. */
    public function useExample(int $index): void
    {
        $example = $this->searchSuggestions[$index] ?? null;

        if ($example === null) {
            return;
        }

        $this->search = $example;

        // Deliberately not `updatedSearch()`: that records the term, and a chip
        // recording itself would make popular suggestions popular by being
        // suggested. Only a query the visitor actually typed counts.
        $this->syncSortToSearch();

        $this->perPage = $this->pageSize;
        $this->resetPage();
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
        $this->syncSortToSearch();

        // Once per settled search, never per keystroke — the debounce on the
        // input is what makes that true. Cheap and non-throwing by contract, so
        // it cannot take the search down with it.
        PopularSearches::record($this->search);
    }

    protected function syncSortToSearch(): void
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
            // Follows the narrowed set, so it has to be recomputed with it.
            $this->latestReviews,
        );
    }

    /**
     * The search and the facets, as a query — the one place either is applied.
     *
     * Both the result grid and the "latest reviews" strip narrow to the same set,
     * so they have to derive it from the same builder; two hand-built copies of
     * this would drift the moment a facet is added.
     */
    protected function filteredCompanies(): Builder
    {
        $query = Company::approved()->matchingSearch($this->search);

        CompanyFacets::apply($query, $this->filters);

        return $query;
    }

    #[Computed]
    public function companyResults()
    {
        $query = $this->filteredCompanies()
            ->withCount(['ratings as ratings_count' => fn ($q) => $q->where('status', 'approved')])
            ->withAvg('approvedRatings as average_rating', 'overall_rating')
            ->with('latestApprovedRating');

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
        // Once the list is narrowed the strip follows it, so it reads as "the
        // newest reviews among these results" rather than advertising companies
        // the user just filtered away. Uncached and unpaginated on purpose: it
        // is a different set per query, and it should reflect the whole match,
        // not just the page that happens to be loaded.
        if ($this->isNarrowed) {
            return Rating::approved()
                ->whereIn('company_id', $this->filteredCompanies()->select('companies.id'))
                ->whereNotNull('review_text')
                ->where('review_text', '!=', '')
                ->with('company')
                ->latest('created_at')
                ->take(3)
                ->get();
        }

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
    {{-- Page header --}}
    <div class="flex flex-col items-start gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-slate-100">جهات التدريب</h1>
            <p class="mt-1.5 text-slate-500 dark:text-slate-400">تقييمات من متدربين سبقوك تساعدك في اختيار جهتك.</p>
        </div>

        <div class="flex shrink-0 items-center gap-3 text-sm text-slate-500 dark:text-slate-400">
            <span class="inline-flex items-baseline gap-1.5">
                <x-public.count-noun :count="$this->stats['ratings']" noun="ratings" :duration="900" class="font-semibold tabular-nums text-slate-900 dark:text-slate-100" />
            </span>
            <span class="size-1 rounded-full bg-slate-300 dark:bg-slate-700" aria-hidden="true"></span>
            <span class="inline-flex items-baseline gap-1.5">
                <x-public.count-noun :count="$this->stats['companies']" noun="companies" :duration="900" class="font-semibold tabular-nums text-slate-900 dark:text-slate-100" />
            </span>
        </div>
    </div>

    {{-- The search box and the facet chips are one control group — grouped so
         they sit close together, rather than inheriting the page rhythm that
         separates unrelated sections. --}}
    <div class="space-y-3">
        {{-- Debounced live search + sort, single row on all viewports.

             The search understands meaning, not just letters — but nothing about a
             plain search box says so, and an unexplained capability is one nobody
             uses. Three cues carry that, cheapest first: a sparked magnifier, a
             placeholder that names what else you can type, and a panel of real
             example queries revealed on focus (ux-principles §5). --}}
        <div class="flex flex-row items-stretch gap-3">
            <div
                class="relative min-w-0 flex-1"
                x-data="{
                    open: false,
                    empty: @js($search === ''),
                    wide: window.matchMedia('(min-width: 640px)').matches,
                }"
                {{-- `empty` is mirrored from the server property so every path that
                     clears the search — the clear button, "مسح الفلاتر", a back
                     navigation — brings the hint panel back, not just typing. --}}
                x-init="$wire.$watch('search', value => empty = value === '')"
                @resize.window.debounce.150ms="wide = window.matchMedia('(min-width: 640px)').matches"
                @click.outside="open = false"
                @keydown.escape="open = false; $refs.search.blur()"
            >
                <span class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-4 text-blue-500 dark:text-blue-400">
                    {{-- Magnifier with a spark: the one glyph that reads as "search, but smarter". --}}
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 20l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18.6 2.9l.62 1.68 1.68.62-1.68.62-.62 1.68-.62-1.68-1.68-.62 1.68-.62z"/>
                    </svg>
                </span>
                <input
                    x-ref="search"
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    @focus="open = true"
                    @input="empty = $event.target.value === ''"
                    {{-- Static value is the no-JS fallback; Alpine swaps in the fuller
                         desktop wording where there is room for it. --}}
                    placeholder="ابحث أو صِف ما تبحث عنه…"
                    :placeholder="wide
                        ? 'ابحث باسم الجهة، أو المدينة، أو صِف المجال الذي تبحث عنه…'
                        : 'ابحث أو صِف ما تبحث عنه…'"
                    {{-- text-base on mobile stops iOS zooming the page on focus; the
                         paddings keep both breakpoints level with the sort button. --}}
                    class="w-full rounded-xl border border-slate-200 bg-white ps-11 pe-11 py-2.5 text-base text-slate-900 placeholder-slate-400 shadow-xs transition-all focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none sm:py-3 sm:text-sm dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100 dark:placeholder-slate-500"
                    aria-label="ابحث عن جهة تدريب بالاسم أو المدينة أو المجال"
                    autocomplete="off"
                    enterkeyhint="search"
                />

                {{-- Clear button --}}
                @if($search !== '')
                    <button
                        type="button"
                        wire:click="$set('search', '')"
                        @click="empty = true"
                        {{-- Shares its corner with the spinner, so it steps aside while one is in flight. --}}
                        wire:loading.remove
                        wire:target="search, useExample, toggleFilter, clearFacet, clearFilters, sort"
                        class="absolute inset-y-0 end-0 flex items-center pe-4 text-slate-400 transition-colors hover:text-slate-600 dark:text-slate-500 dark:hover:text-slate-300"
                        aria-label="مسح البحث"
                    >
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                @endif

                {{-- Inline spinner while the search request is in flight --}}
                <div wire:loading wire:target="search, useExample, toggleFilter, clearFacet, clearFilters, sort" class="absolute inset-y-0 end-0 flex items-center pe-4 text-slate-400 dark:text-slate-500">
                    <svg class="size-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                </div>

                {{-- Revealed on focus while empty, so it teaches at the moment of
                     typing and costs nothing the rest of the time. Overlays rather
                     than pushes, which keeps the results grid still on mobile. --}}
                @if($this->searchSuggestions !== [])
                    {{-- Visibility is a class binding rather than x-show: this panel
                         lives inside a Livewire-morphed tree, where an x-show effect
                         can be replaced mid-life and stop re-running, leaving the
                         panel stuck shut. `invisible` also keeps it out of the
                         accessibility tree and untabbable while closed. --}}
                    {{-- Visibility is a `hidden`/`block` class binding, not x-show:
                         inside a Livewire-morphed tree an x-show effect can be
                         replaced mid-life and stop re-running, pinning the panel
                         shut. The toggled utilities live only in the binding —
                         Alpine removes classes it added but never ones hard-coded
                         in `class`, so a static `hidden` would out-rank the bound
                         `block`. x-cloak covers the pre-Alpine frame. --}}
                    <div
                        x-cloak
                        :class="open && empty ? 'block' : 'hidden'"
                        class="absolute inset-x-0 top-full z-20 mt-2 rounded-xl border border-slate-200 bg-white p-4 shadow-lg dark:border-slate-800 dark:bg-slate-900"
                    >
                        <p class="text-xs font-medium text-slate-400 dark:text-slate-500">جرّب البحث بـ</p>

                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach($this->searchSuggestions as $index => $example)
                                <button
                                    type="button"
                                    wire:click="useExample({{ $index }})"
                                    @click="open = false; empty = false"
                                    class="rounded-full border border-slate-200 px-3 py-1.5 text-sm text-slate-600 transition-colors hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700 dark:border-slate-700 dark:text-slate-300 dark:hover:border-blue-800 dark:hover:bg-blue-950/40 dark:hover:text-blue-300"
                                >{{ $example }}</button>
                            @endforeach
                        </div>

                        <p class="mt-3 border-t border-slate-100 pt-3 text-xs leading-relaxed text-slate-500 dark:border-slate-800 dark:text-slate-400">
                            البحث يفهم المعنى، لا الحروف فقط — اكتب اسم الجهة، أو المدينة، أو صِف المجال ولو بكلماتك.
                        </p>
                    </div>
                @endif
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
    </div>

    {{-- "Latest reviews" activity strip --}}
    @if($this->latestReviews->isNotEmpty())
        <div class="space-y-3">
            <div class="flex items-center gap-2">
                <span class="size-2 rounded-full bg-emerald-500 motion-safe:animate-pulse" aria-hidden="true"></span>
                <h2 class="text-sm font-semibold text-slate-500 dark:text-slate-400">{{ $this->isNarrowed ? 'أحدث التقييمات في النتائج' : 'أحدث التقييمات' }}</h2>
            </div>
            <div class="flex gap-3 overflow-x-auto snap-x snap-mandatory -mx-4 px-4 pb-2 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden sm:mx-0 sm:grid sm:grid-cols-3 sm:gap-4 sm:overflow-visible sm:px-0">
                @foreach($this->latestReviews as $review)
                    <a
                        wire:key="latest-review-{{ $review->id }}"
                        href="{{ route('companies.show', $review->company) }}"
                        wire:navigate
                        class="group w-[78%] shrink-0 snap-start rounded-xl border border-slate-200/80 bg-white p-5 shadow-xs transition-all duration-200 hover:border-blue-200 hover:shadow-md motion-safe:hover:-translate-y-0.5 sm:w-auto dark:border-slate-800 dark:bg-slate-900 dark:hover:border-blue-900"
                    >
                        <p class="text-sm leading-relaxed text-slate-600 dark:text-slate-400">
                            <span class="text-lg font-bold leading-none text-blue-300 dark:text-blue-500" aria-hidden="true">&rdquo;</span>
                            {{ Str::limit($review->review_text, 110) }}
                        </p>
                        <div class="mt-3 flex items-center justify-between gap-2 text-xs text-slate-400 dark:text-slate-500">
                            <span class="min-w-0 truncate">
                                {{ $review->role_title }} ·
                                <span class="font-medium text-slate-500 transition-colors group-hover:text-blue-600 dark:text-slate-400 dark:group-hover:text-blue-400">{{ $review->company->name }}</span>
                            </span>
                            <span class="shrink-0">{{ $review->created_at->diffForHumans() }}</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Immediate feedback that the list is being rebuilt. No `.delay`: pairing
         a delay modifier with a display modifier does not parse, and Livewire
         silently leaves the element unbound — which renders the skeleton
         permanently. `.grid` alone is the pattern the sentinel below uses. --}}
    <div wire:loading.grid wire:target="search, useExample, toggleFilter, clearFacet, clearFilters, sort" class="grid grid-cols-1 gap-4 md:grid-cols-2" aria-hidden="true">
        <x-public.company-card-skeleton :count="4" />
    </div>

    <div wire:loading.remove wire:target="search, useExample, toggleFilter, clearFacet, clearFilters, sort" class="space-y-8">
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
    </div>

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
