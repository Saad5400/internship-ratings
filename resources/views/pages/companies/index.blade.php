<?php

use App\Models\Company;
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

    public int $perPage = 12;

    protected int $pageSize = 12;

    /**
     * @return array<int, array{id: string, name: string}>
     */
    public function getSortOptionsProperty(): array
    {
        return [
            ['id' => 'highest_rated', 'name' => 'الأعلى تقييماً'],
            ['id' => 'most_rated', 'name' => 'الأكثر تقييماً'],
            ['id' => 'most_recently_rated', 'name' => 'الأحدث تقييماً'],
        ];
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
        if (! in_array($value, ['highest_rated', 'most_rated', 'most_recently_rated'], true)) {
            $this->sort = 'highest_rated';
        }
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
        unset($this->companyResults, $this->companies, $this->hasMore);
    }

    #[Computed]
    public function companyResults()
    {
        $query = Company::approved()
            ->withCount('ratings')
            ->searchByName($this->search);

        match ($this->sort) {
            'most_rated' => $query->orderByDesc('ratings_count'),
            'most_recently_rated' => $query->orderByRaw(
                '(select max(created_at) from ratings where ratings.company_id = companies.id) desc nulls last'
            ),
            default => $query->orderByRaw(
                '(select avg(overall_rating) from ratings where ratings.company_id = companies.id) desc nulls last'
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

    #[Computed]
    public function hasMore(): bool
    {
        return $this->companyResults->count() > $this->perPage;
    }
}; ?>

<div class="space-y-8">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">الجهات</h1>
            <p class="mt-2 text-slate-500">تصفح تقييمات التدريب في مختلف الجهات</p>
        </div>
    </div>

    {{-- Debounced live search + sort, single row on all viewports --}}
    <div class="flex flex-row items-stretch gap-3">
        <div class="relative min-w-0 flex-1">
            <span class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-4 text-slate-400">
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </span>
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="ابحث عن جهة..."
                class="w-full rounded-xl border border-slate-200 bg-white ps-11 pe-11 py-3 text-sm text-slate-900 placeholder-slate-400 shadow-xs transition-all focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none"
                aria-label="ابحث عن جهة"
            />

            {{-- Clear button --}}
            @if($search !== '')
                <button
                    type="button"
                    wire:click="$set('search', '')"
                    class="absolute inset-y-0 end-0 flex items-center pe-4 text-slate-400 transition-colors hover:text-slate-600"
                    aria-label="مسح البحث"
                >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            @endif

            {{-- Inline spinner while the search request is in flight --}}
            <div wire:loading wire:target="search" class="absolute inset-y-0 end-0 flex items-center pe-4 text-slate-400">
                <svg class="size-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
            </div>
        </div>

        <div class="relative size-[46px] shrink-0 compact-select-trigger" wire:key="sort-select-wrapper" title="ترتيب حسب">
            <span class="pointer-events-none absolute inset-0 z-10 flex items-center justify-center text-slate-500">
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
                    <div class="p-3 border-s-4 border-s-transparent hover:bg-slate-50">
                        <div class="font-medium text-slate-900">{{ data_get($option, 'name') }}</div>
                    </div>
                @endscope
            </x-public.nice-select>
        </div>
    </div>

    @if($this->companies->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-200 bg-white py-16 text-center">
            <svg class="mx-auto size-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            <p class="mt-4 text-sm text-slate-500">لا توجد جهات {{ $search !== '' ? 'تطابق بحثك' : 'حالياً' }}</p>
            @if($search !== '')
                <button type="button" wire:click="$set('search', '')" class="mt-3 text-sm font-medium text-blue-500 transition-colors hover:text-blue-600">مسح البحث</button>
            @endif
        </div>
    @else
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            @foreach($this->companies as $company)
                <div wire:key="company-{{ $company->id }}">
                    <x-public.company-card :company="$company" />
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
