<?php

use App\Enums\CompanyType;
use App\Enums\Modality;
use App\Enums\Recommendation;
use App\Livewire\Concerns\ModeratesRecords;
use App\Models\Rating;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/*
 * The full ratings workbench: status tabs + quick views up front (clearing the
 * queue is the daily job), search and rarely-needed filters behind progressive
 * disclosure, and one-click approve/reject on every row.
 */
new #[Layout('layouts.admin')] #[Title('التقييمات')] class extends Component {
    use ModeratesRecords;
    use WithPagination;

    /** @var list<string> */
    public const TAB_KEYS = ['all', 'pending', 'approved', 'rejected', 'recommended', 'high'];

    /** @var list<string> */
    public const SORTABLE_FIELDS = ['created_at', 'overall_rating'];

    #[Url(as: 'tab', except: 'all')]
    public string $tab = 'all';

    #[Url(as: 'search', except: '')]
    public string $search = '';

    #[Url(as: 'modality', except: '')]
    public ?string $modality = '';

    #[Url(as: 'recommendation', except: '')]
    public ?string $recommendation = '';

    #[Url(as: 'company_type', except: '')]
    public ?string $companyType = '';

    #[Url(as: 'job_offer', except: '')]
    public ?string $jobOffer = '';

    #[Url(as: 'score', except: '')]
    public ?string $score = '';

    #[Url(as: 'sort', except: 'created_at')]
    public string $sortField = 'created_at';

    #[Url(as: 'direction', except: 'desc')]
    public string $sortDirection = 'desc';

    public function mount(): void
    {
        // Whitelist everything that can arrive via the query string.
        if (! in_array($this->tab, self::TAB_KEYS, true)) {
            $this->tab = 'all';
        }

        if (! in_array($this->sortField, self::SORTABLE_FIELDS, true)) {
            $this->sortField = 'created_at';
        }

        if (! in_array($this->sortDirection, ['asc', 'desc'], true)) {
            $this->sortDirection = 'desc';
        }

        $this->modality = in_array($this->modality, Modality::values(), true) ? $this->modality : '';
        $this->recommendation = in_array($this->recommendation, Recommendation::values(), true) ? $this->recommendation : '';
        $this->companyType = in_array($this->companyType, CompanyType::values(), true) ? $this->companyType : '';
        $this->jobOffer = in_array($this->jobOffer, ['yes', 'no'], true) ? $this->jobOffer : '';
        $this->score = in_array($this->score, ['1', '2', '3', '4', '5'], true) ? $this->score : '';
    }

    public function setTab(string $key): void
    {
        if (! in_array($key, self::TAB_KEYS, true)) {
            return;
        }

        $this->tab = $key;
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if (! in_array($field, self::SORTABLE_FIELDS, true)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'desc' ? 'asc' : 'desc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'desc';
        }

        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->modality = '';
        $this->recommendation = '';
        $this->companyType = '';
        $this->jobOffer = '';
        $this->score = '';
        $this->resetPage();
    }

    /** Empty-state recovery: back to the full, unfiltered list. */
    public function clearSearchAndFilters(): void
    {
        $this->search = '';
        $this->clearFilters();
    }

    public function updated(string $property, mixed $value): void
    {
        $filterProperties = ['search', 'modality', 'recommendation', 'companyType', 'jobOffer', 'score'];

        if (! in_array($property, $filterProperties, true)) {
            return;
        }

        // The nice-select clear button resets the bound value to null.
        if ($value === null) {
            $this->{$property} = '';
        }

        $this->resetPage();
    }

    protected function afterModeration(): void
    {
        unset($this->ratings, $this->pendingCount, $this->approvedCount, $this->rejectedCount);
    }

    #[Computed]
    public function ratings()
    {
        $query = Rating::query()
            ->with('company')
            ->when($this->tab === 'pending', fn ($q) => $q->pending())
            ->when($this->tab === 'approved', fn ($q) => $q->approved())
            ->when($this->tab === 'rejected', fn ($q) => $q->where('ratings.status', 'rejected'))
            ->when($this->tab === 'recommended', fn ($q) => $q->where('recommendation', Recommendation::Yes))
            ->when($this->tab === 'high', fn ($q) => $q->where('overall_rating', '>=', 4))
            ->when(trim($this->search) !== '', function ($q) {
                $term = trim($this->search);

                $q->where(function ($q) use ($term) {
                    $q->whereHas('company', fn ($company) => $company->searchByName($term))
                        ->orWhere('role_title', 'like', "%{$term}%")
                        ->orWhere('reviewer_name', 'like', "%{$term}%")
                        ->orWhere('city', 'like', "%{$term}%")
                        ->orWhere('department', 'like', "%{$term}%");
                });
            })
            ->when(filled($this->modality), fn ($q) => $q->where('modality', $this->modality))
            ->when(filled($this->recommendation), fn ($q) => $q->where('recommendation', $this->recommendation))
            ->when(filled($this->companyType), fn ($q) => $q->whereHas('company', fn ($company) => $company->where('type', $this->companyType)))
            ->when(filled($this->jobOffer), fn ($q) => $q->where('job_offer', $this->jobOffer === 'yes'))
            ->when(filled($this->score), fn ($q) => $q->whereRaw('ROUND(overall_rating) = ?', [(int) $this->score]));

        if ($this->sortField === 'overall_rating') {
            // Direction is whitelisted in sortBy()/mount(), so the raw sort is safe.
            $query->orderByRaw("overall_rating {$this->sortDirection} nulls last");
        } else {
            $query->orderBy($this->sortField, $this->sortDirection);
        }

        return $query->orderByDesc('id')->paginate(15);
    }

    #[Computed]
    public function pendingCount(): int
    {
        return Rating::pending()->count();
    }

    #[Computed]
    public function approvedCount(): int
    {
        return Rating::approved()->count();
    }

    #[Computed]
    public function rejectedCount(): int
    {
        return Rating::where('status', 'rejected')->count();
    }

    /**
     * @return list<array{key: string, label: string, count: ?int, color: ?string}>
     */
    #[Computed]
    public function tabs(): array
    {
        return [
            ['key' => 'all', 'label' => 'الكل', 'count' => null, 'color' => null],
            ['key' => 'pending', 'label' => 'قيد المراجعة', 'count' => $this->pendingCount, 'color' => 'warning'],
            ['key' => 'approved', 'label' => 'موافق عليها', 'count' => $this->approvedCount, 'color' => 'success'],
            ['key' => 'rejected', 'label' => 'مرفوضة', 'count' => $this->rejectedCount, 'color' => 'danger'],
            ['key' => 'recommended', 'label' => 'موصى بها', 'count' => null, 'color' => null],
            ['key' => 'high', 'label' => 'تقييم عالي', 'count' => null, 'color' => null],
        ];
    }

    #[Computed]
    public function activeFilterCount(): int
    {
        return collect([$this->modality, $this->recommendation, $this->companyType, $this->jobOffer, $this->score])
            ->filter(fn (?string $value): bool => filled($value))
            ->count();
    }

    /**
     * @param  array<string, string>  $options
     * @return list<array{id: string, name: string}>
     */
    protected function toChoices(array $options): array
    {
        return collect($options)
            ->map(fn (string $label, string $value): array => ['id' => $value, 'name' => $label])
            ->values()
            ->all();
    }

    /** @return list<array{id: string, name: string}> */
    #[Computed]
    public function modalityChoices(): array
    {
        return $this->toChoices(Modality::options());
    }

    /** @return list<array{id: string, name: string}> */
    #[Computed]
    public function recommendationChoices(): array
    {
        return $this->toChoices(Recommendation::options());
    }

    /** @return list<array{id: string, name: string}> */
    #[Computed]
    public function companyTypeChoices(): array
    {
        return $this->toChoices(CompanyType::options());
    }

    /** @return list<array{id: string, name: string}> */
    #[Computed]
    public function jobOfferChoices(): array
    {
        return [
            ['id' => 'yes', 'name' => 'نعم'],
            ['id' => 'no', 'name' => 'لا'],
        ];
    }

    /** @return list<array{id: string, name: string}> */
    #[Computed]
    public function scoreChoices(): array
    {
        return collect(range(5, 1))
            ->map(fn (int $score): array => ['id' => (string) $score, 'name' => $score.' / 5'])
            ->all();
    }
}; ?>

<div class="space-y-6">
    <x-admin.page-header
        title="التقييمات"
        subtitle="كل تقييمات المتدربين: راجعها، عدّلها، ووافق عليها من مكان واحد"
    />

    <x-admin.status-tabs :tabs="$this->tabs" :active="$tab" />

    {{-- Debounced live search + filters behind progressive disclosure --}}
    <div x-data="{ showFilters: {{ $this->activeFilterCount > 0 ? 'true' : 'false' }} }" class="space-y-3">
        <div class="flex flex-row items-stretch gap-3">
            <div class="relative min-w-0 flex-1">
                <span class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-4 text-slate-400">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </span>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="ابحث بالجهة، الدور، المدينة، أو اسم المقيّم..."
                    class="w-full rounded-xl border border-slate-200 bg-white ps-11 pe-11 py-3 text-sm text-slate-900 placeholder-slate-400 shadow-xs transition-all focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none"
                    aria-label="ابحث في التقييمات"
                />

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

            <button
                type="button"
                @click="showFilters = ! showFilters"
                :aria-expanded="showFilters"
                class="inline-flex shrink-0 items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 shadow-xs transition-colors hover:bg-slate-50 hover:text-slate-900"
            >
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                تصفية
                @if($this->activeFilterCount > 0)
                    <x-badge color="primary">{{ $this->activeFilterCount }}</x-badge>
                @endif
            </button>
        </div>

        <div x-show="showFilters" x-cloak class="rounded-2xl border border-slate-200 bg-white p-4 shadow-xs">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
                <x-public.nice-select
                    label="نمط الحضور"
                    name="modality"
                    wire:model.live="modality"
                    :options="$this->modalityChoices"
                    placeholder="الكل"
                    offline
                />
                <x-public.nice-select
                    label="التوصية"
                    name="recommendation"
                    wire:model.live="recommendation"
                    :options="$this->recommendationChoices"
                    placeholder="الكل"
                    offline
                />
                <x-public.nice-select
                    label="نوع الجهة"
                    name="companyType"
                    wire:model.live="companyType"
                    :options="$this->companyTypeChoices"
                    placeholder="الكل"
                    offline
                />
                <x-public.nice-select
                    label="عرض عمل"
                    name="jobOffer"
                    wire:model.live="jobOffer"
                    :options="$this->jobOfferChoices"
                    placeholder="الكل"
                    offline
                />
                <x-public.nice-select
                    label="التقييم"
                    name="score"
                    wire:model.live="score"
                    :options="$this->scoreChoices"
                    placeholder="الكل"
                    offline
                />
            </div>

            @if($this->activeFilterCount > 0)
                <button
                    type="button"
                    wire:click="clearFilters"
                    class="mt-3 inline-flex items-center gap-1.5 text-sm font-medium text-blue-600 transition-colors hover:text-blue-700"
                >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    مسح التصفية
                </button>
            @endif
        </div>
    </div>

    @if($this->ratings->isEmpty())
        @php
            $isNarrowed = trim($search) !== '' || $this->activeFilterCount > 0;
        @endphp
        <x-admin.empty-state
            :message="$isNarrowed
                ? 'لا توجد تقييمات تطابق بحثك أو التصفية الحالية'
                : ($tab !== 'all' ? 'لا توجد تقييمات ضمن هذا العرض حالياً' : 'لا توجد تقييمات بعد — ستظهر هنا فور وصولها')"
        >
            @if($isNarrowed)
                <button type="button" wire:click="clearSearchAndFilters" class="text-sm font-medium text-blue-500 transition-colors hover:text-blue-600">
                    مسح البحث والتصفية
                </button>
            @elseif($tab !== 'all')
                <button type="button" wire:click="setTab('all')" class="text-sm font-medium text-blue-500 transition-colors hover:text-blue-600">
                    عرض كل التقييمات
                </button>
            @endif
        </x-admin.empty-state>
    @else
        <x-admin.table>
            <x-slot:head>
                <x-admin.th>الجهة</x-admin.th>
                <x-admin.th>الدور</x-admin.th>
                <x-admin.th>الحالة</x-admin.th>
                <x-admin.th field="overall_rating" :sort="$sortField" :direction="$sortDirection">التقييم</x-admin.th>
                <x-admin.th>التوصية</x-admin.th>
                <x-admin.th field="created_at" :sort="$sortField" :direction="$sortDirection">التاريخ</x-admin.th>
                <x-admin.th align="end"><span class="sr-only">الإجراءات</span></x-admin.th>
            </x-slot:head>

            @foreach($this->ratings as $rating)
                <tr wire:key="rating-row-{{ $rating->id }}" class="transition-colors hover:bg-slate-50/60">
                    <td class="px-4 py-3">
                        @if($rating->company !== null)
                            <a
                                href="{{ route('admin.companies.show', $rating->company) }}"
                                wire:navigate
                                class="font-bold text-slate-900 transition-colors hover:text-blue-600"
                            >{{ $rating->company->name }}</a>
                        @else
                            <span class="text-slate-400">جهة غير معروفة</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="text-slate-700">{{ $rating->role_title ?? 'بدون مسمى' }}</div>
                        @if(filled($rating->department))
                            <div class="mt-0.5 text-xs text-slate-400">{{ $rating->department }}</div>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <x-public.status-badge :status="$rating->status" />
                    </td>
                    <td class="px-4 py-3">
                        <x-admin.score-badge :score="$rating->overall_rating" />
                    </td>
                    <td class="px-4 py-3">
                        @if($rating->recommendation !== null)
                            <x-badge :color="$rating->recommendation->color()">{{ $rating->recommendation->label() }}</x-badge>
                        @else
                            <span class="text-xs text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 text-slate-500" title="{{ $rating->created_at->format('Y-m-d H:i') }}">
                        {{ $rating->created_at->diffForHumans() }}
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-2">
                            <x-admin.moderation-actions :record="$rating" type="rating" />
                            <a
                                href="{{ route('admin.ratings.edit', $rating) }}"
                                wire:navigate
                                class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-100 hover:text-slate-900"
                            >
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                تعديل
                            </a>
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-admin.table>

        <div>
            <x-admin.pagination :paginator="$this->ratings" />
        </div>
    @endif
</div>
