<?php

use App\Enums\CompanyType;
use App\Livewire\Concerns\ModeratesRecords;
use App\Models\Company;
use App\Support\ModerationStatus;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/*
 * Companies list: status tabs, normalized-Arabic live search, progressive
 * filters, and a sortable table with one-click moderation per row. Creating
 * a company is a light-object dialog — no dedicated page needed.
 */
new #[Layout('layouts.admin')] #[Title('الجهات')] class extends Component
{
    use ModeratesRecords;
    use WithPagination;

    private const SORTABLE_FIELDS = ['created_at', 'ratings_total_count', 'approved_ratings_avg'];

    #[Url(as: 'search', except: '')]
    public string $search = '';

    #[Url(as: 'tab', except: 'all')]
    public string $tab = 'all';

    #[Url(as: 'type', except: 'all')]
    public string $typeFilter = 'all';

    #[Url(as: 'ratings', except: 'all')]
    public string $ratingsFilter = 'all';

    #[Url(as: 'sort', except: 'created_at')]
    public string $sortField = 'created_at';

    #[Url(as: 'direction', except: 'desc')]
    public string $sortDirection = 'desc';

    /**
     * Create-company dialog state. Status defaults to approved: an admin
     * adding a company by hand is vouching for it.
     */
    public ?string $createName = null;

    public ?string $createType = null;

    public ?string $createWebsite = null;

    public ?string $createDescription = null;

    public string $createStatus = 'approved';

    public function mount(): void
    {
        $this->sanitizeUrlState();

        // The show page redirects here after a delete with a flashed outcome.
        if (session()->has('toast')) {
            $this->dispatch('toast', message: session('toast'), type: 'success');
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(mixed $value): void
    {
        if ($value !== 'all' && ! in_array($value, CompanyType::values(), true)) {
            $this->typeFilter = 'all';
        }

        $this->resetPage();
    }

    public function updatedRatingsFilter(mixed $value): void
    {
        if (! in_array($value, ['all', 'yes', 'no'], true)) {
            $this->ratingsFilter = 'all';
        }

        $this->resetPage();
    }

    public function setTab(string $tab): void
    {
        if ($tab !== 'all' && ! array_key_exists($tab, ModerationStatus::options())) {
            return;
        }

        $this->tab = $tab;
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
        $this->search = '';
        $this->tab = 'all';
        $this->typeFilter = 'all';
        $this->ratingsFilter = 'all';

        $this->resetPage();
    }

    public function createCompany(): void
    {
        $this->normalizeCreateForm();

        $this->validate([
            'createName' => ['required', 'string', 'max:255'],
            'createType' => ['nullable', 'string', Rule::in(CompanyType::values())],
            'createWebsite' => ['nullable', 'string', 'url', 'max:255'],
            'createDescription' => ['nullable', 'string', 'max:2000'],
            'createStatus' => ['required', 'string', Rule::in(array_keys(ModerationStatus::options()))],
        ], [
            'createName.required' => 'اسم الجهة مطلوب.',
            'createName.max' => 'اسم الجهة طويل جداً.',
            'createType.in' => 'نوع الجهة غير صالح.',
            'createWebsite.url' => 'يرجى إدخال رابط صحيح يبدأ بـ http أو https.',
            'createWebsite.max' => 'رابط الموقع طويل جداً.',
            'createDescription.max' => 'الوصف طويل جداً (الحد الأقصى 2000 حرف).',
            'createStatus.required' => 'حالة الجهة مطلوبة.',
            'createStatus.in' => 'حالة الجهة غير صالحة.',
        ]);

        Company::create([
            'name' => $this->createName,
            'type' => $this->createType,
            'website' => $this->createWebsite,
            'description' => $this->createDescription,
            'status' => $this->createStatus,
        ]);

        $this->reset('createName', 'createType', 'createWebsite', 'createDescription');
        $this->createStatus = 'approved';
        $this->resetValidation();
        $this->resetPage();
        unset($this->companies, $this->statusCounts);

        $this->dispatch('close-dialog', id: 'create-company');
        $this->dispatch('toast', message: 'تمت إضافة الجهة', type: 'success');
    }

    protected function afterModeration(): void
    {
        unset($this->companies, $this->statusCounts);
    }

    #[Computed]
    public function companies()
    {
        $query = Company::query()
            ->withCount('ratings as ratings_total_count')
            ->withAvg(['ratings as approved_ratings_avg' => fn ($q) => $q->where('status', 'approved')], 'overall_rating')
            ->searchByName($this->search)
            ->when($this->tab !== 'all', fn ($q) => $q->where('companies.status', $this->tab))
            ->when($this->typeFilter !== 'all', fn ($q) => $q->where('type', $this->typeFilter))
            ->when($this->ratingsFilter === 'yes', fn ($q) => $q->has('ratings'))
            ->when($this->ratingsFilter === 'no', fn ($q) => $q->doesntHave('ratings'));

        if ($this->sortField === 'approved_ratings_avg') {
            // Companies without approved ratings sort after rated ones either way.
            $query->orderByRaw('approved_ratings_avg '.$this->sortDirection.' nulls last');
        } else {
            $query->orderBy($this->sortField, $this->sortDirection);
        }

        return $query->orderByDesc('companies.id')->paginate(15);
    }

    /**
     * Global per-status totals for the tab badges — the queue size is the
     * signal, so counts ignore search and filters on purpose.
     *
     * @return array<string, int>
     */
    #[Computed]
    public function statusCounts(): array
    {
        return Company::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($total): int => (int) $total)
            ->all();
    }

    /**
     * @return array<int, array{id: string, name: string}>
     */
    #[Computed]
    public function typeOptions(): array
    {
        return collect(CompanyType::options())
            ->map(fn (string $label, string $value): array => ['id' => $value, 'name' => $label])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id: string, name: string}>
     */
    #[Computed]
    public function statusOptions(): array
    {
        return collect(ModerationStatus::options())
            ->map(fn (string $label, string $value): array => ['id' => $value, 'name' => $label])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id: string, name: string}>
     */
    #[Computed]
    public function typeFilterOptions(): array
    {
        return [['id' => 'all', 'name' => 'كل الأنواع'], ...$this->typeOptions];
    }

    /**
     * @return array<int, array{id: string, name: string}>
     */
    #[Computed]
    public function ratingsFilterOptions(): array
    {
        return [
            ['id' => 'all', 'name' => 'الكل'],
            ['id' => 'yes', 'name' => 'لديها تقييمات'],
            ['id' => 'no', 'name' => 'بدون تقييمات'],
        ];
    }

    #[Computed]
    public function isNarrowed(): bool
    {
        return $this->search !== ''
            || $this->tab !== 'all'
            || $this->typeFilter !== 'all'
            || $this->ratingsFilter !== 'all';
    }

    /** Guard state arriving through the URL against tampering. */
    protected function sanitizeUrlState(): void
    {
        if (! in_array($this->sortField, self::SORTABLE_FIELDS, true)) {
            $this->sortField = 'created_at';
        }

        if (! in_array($this->sortDirection, ['asc', 'desc'], true)) {
            $this->sortDirection = 'desc';
        }

        if ($this->tab !== 'all' && ! array_key_exists($this->tab, ModerationStatus::options())) {
            $this->tab = 'all';
        }

        if ($this->typeFilter !== 'all' && ! in_array($this->typeFilter, CompanyType::values(), true)) {
            $this->typeFilter = 'all';
        }

        if (! in_array($this->ratingsFilter, ['all', 'yes', 'no'], true)) {
            $this->ratingsFilter = 'all';
        }
    }

    /** Blank strings from the dialog inputs become nulls before validation. */
    protected function normalizeCreateForm(): void
    {
        foreach (['createName', 'createType', 'createWebsite', 'createDescription'] as $field) {
            $value = trim((string) $this->{$field});
            $this->{$field} = $value === '' ? null : $value;
        }
    }
}; ?>

<div class="space-y-6">
    @php
        $inputClass = 'w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm text-slate-900 shadow-xs transition-all focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none';
    @endphp

    <x-admin.page-header title="الجهات" subtitle="إدارة جهات التدريب: مراجعة، بحث، وإضافة">
        <x-admin.dialog id="create-company" title="إضافة جهة">
            <x-slot:trigger>
                <button type="button" class="inline-flex items-center gap-1.5 rounded-lg bg-blue-500 px-4 py-2.5 text-sm font-semibold text-white shadow-xs transition-all hover:bg-blue-600 active:scale-[0.98]">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    إضافة جهة
                </button>
            </x-slot:trigger>

            <form wire:submit="createCompany" class="space-y-4">
                <x-public.form-field label="اسم الجهة" name="createName" required>
                    <input type="text" id="createName" wire:model="createName" placeholder="مثلاً: شركة أرامكو السعودية" class="{{ $inputClass }}" />
                </x-public.form-field>

                <x-public.form-field label="النوع" name="createType">
                    <x-public.nice-select
                        name="createType"
                        wire:model="createType"
                        :options="$this->typeOptions"
                        placeholder="اختر النوع..."
                        offline
                    />
                </x-public.form-field>

                <x-public.form-field label="الموقع الإلكتروني" name="createWebsite">
                    <input type="url" id="createWebsite" wire:model="createWebsite" dir="ltr" placeholder="https://example.com" class="{{ $inputClass }} text-start" />
                </x-public.form-field>

                <x-public.form-field label="الوصف" name="createDescription">
                    <textarea id="createDescription" wire:model="createDescription" rows="3" placeholder="نبذة مختصرة عن الجهة" class="{{ $inputClass }}"></textarea>
                </x-public.form-field>

                <x-public.form-field label="الحالة" name="createStatus" required>
                    <x-public.nice-select
                        name="createStatus"
                        wire:model="createStatus"
                        :options="$this->statusOptions"
                        offline
                        :clearable="false"
                    />
                </x-public.form-field>

                <div class="flex items-center justify-end gap-2 pt-1">
                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        wire:target="createCompany"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-blue-500 px-4 py-2.5 text-sm font-semibold text-white shadow-xs transition-all hover:bg-blue-600 active:scale-[0.98] disabled:opacity-60"
                    >
                        <svg wire:loading wire:target="createCompany" class="size-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                        إضافة الجهة
                    </button>
                </div>
            </form>
        </x-admin.dialog>
    </x-admin.page-header>

    @php
        $counts = $this->statusCounts;
        $tabs = [
            ['key' => 'all', 'label' => 'الكل', 'count' => null],
            ['key' => 'pending', 'label' => 'قيد المراجعة', 'count' => $counts['pending'] ?? 0, 'color' => 'warning'],
            ['key' => 'approved', 'label' => 'موافق عليها', 'count' => $counts['approved'] ?? 0, 'color' => 'success'],
            ['key' => 'rejected', 'label' => 'مرفوضة', 'count' => $counts['rejected'] ?? 0, 'color' => 'danger'],
        ];
    @endphp

    <x-admin.status-tabs :tabs="$tabs" :active="$tab" />

    <div x-data="{ showFilters: @js($typeFilter !== 'all' || $ratingsFilter !== 'all') }" class="space-y-3">
        <div class="flex flex-row items-stretch gap-3">
            {{-- Debounced live search over the normalized Arabic name --}}
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

                {{-- Inline spinner scoped to the search request only --}}
                <div wire:loading wire:target="search" class="absolute inset-y-0 end-0 flex items-center pe-4 text-slate-400">
                    <svg class="size-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                </div>
            </div>

            {{-- Filters live behind a toggle: the daily job is the queue, not slicing --}}
            <button
                type="button"
                @click="showFilters = ! showFilters"
                :class="showFilters ? 'border-blue-200 bg-blue-50 text-blue-600' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
                class="inline-flex shrink-0 items-center gap-1.5 rounded-xl border px-4 py-3 text-sm font-medium shadow-xs transition-colors"
                aria-label="إظهار أو إخفاء الفلاتر"
            >
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                تصفية
                @if($typeFilter !== 'all' || $ratingsFilter !== 'all')
                    <x-badge color="primary">{{ ($typeFilter !== 'all' ? 1 : 0) + ($ratingsFilter !== 'all' ? 1 : 0) }}</x-badge>
                @endif
            </button>
        </div>

        <div x-show="showFilters" x-cloak class="grid grid-cols-1 gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-xs sm:grid-cols-2">
            <x-public.form-field label="نوع الجهة" name="typeFilter">
                <x-public.nice-select
                    name="typeFilter"
                    wire:model.live="typeFilter"
                    :options="$this->typeFilterOptions"
                    offline
                    :clearable="false"
                    aria-label="تصفية حسب نوع الجهة"
                />
            </x-public.form-field>

            <x-public.form-field label="لديها تقييمات" name="ratingsFilter">
                <x-public.nice-select
                    name="ratingsFilter"
                    wire:model.live="ratingsFilter"
                    :options="$this->ratingsFilterOptions"
                    offline
                    :clearable="false"
                    aria-label="تصفية حسب وجود تقييمات"
                />
            </x-public.form-field>
        </div>
    </div>

    @if($this->companies->isEmpty())
        <x-admin.empty-state
            icon="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
            :message="$this->isNarrowed ? 'لا توجد جهات تطابق بحثك أو الفلاتر المحددة' : 'لا توجد جهات بعد — أضف أول جهة من زر «إضافة جهة» أعلاه'"
        >
            @if($this->isNarrowed)
                <button type="button" wire:click="clearFilters" class="text-sm font-medium text-blue-500 transition-colors hover:text-blue-600">
                    مسح البحث والفلاتر
                </button>
            @endif
        </x-admin.empty-state>
    @else
        <x-admin.table>
            <x-slot:head>
                <x-admin.th>الاسم</x-admin.th>
                <x-admin.th>النوع</x-admin.th>
                <x-admin.th>الحالة</x-admin.th>
                <x-admin.th field="ratings_total_count" :sort="$sortField" :direction="$sortDirection">التقييمات</x-admin.th>
                <x-admin.th field="approved_ratings_avg" :sort="$sortField" :direction="$sortDirection">متوسط التقييم</x-admin.th>
                <x-admin.th field="created_at" :sort="$sortField" :direction="$sortDirection">التاريخ</x-admin.th>
                <x-admin.th><span class="sr-only">إجراءات</span></x-admin.th>
            </x-slot:head>

            @foreach($this->companies as $company)
                @php
                    $host = $company->website ? (parse_url($company->website, PHP_URL_HOST) ?: $company->website) : null;
                    $secondary = $host ?? \Illuminate\Support\Str::limit($company->description, 60);
                    // Duplicate detection costs a query, so it runs only for the pending rows it can help with.
                    $duplicateOf = $company->status === 'pending' ? $company->findSimilarApproved() : null;
                    $linkedRatings = (int) $company->ratings_total_count;
                    $confirmReject = $linkedRatings > 0
                        ? 'لدى هذه الجهة '.trans_choice('counts.ratings', $linkedRatings).'؛ برفضها ستُخفى هي وتقييماتها من الموقع. يمكنك أولاً نقل التقييمات إلى جهة أخرى من بطاقة كل تقييم.'
                        : null;
                    $rowAverage = $company->approved_ratings_avg === null ? null : (float) $company->approved_ratings_avg;
                @endphp
                <tr wire:key="company-row-{{ $company->id }}" class="transition-colors hover:bg-slate-50/60">
                    <td class="max-w-72 px-4 py-3">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('admin.companies.show', $company) }}" wire:navigate class="truncate font-semibold text-slate-900 transition-colors hover:text-blue-600">
                                {{ $company->name }}
                            </a>
                            @if($duplicateOf !== null)
                                <x-badge color="warning" title="قد تكون نسخة مكررة من «{{ $duplicateOf->name }}»">مكررة؟</x-badge>
                            @endif
                        </div>
                        @if($secondary)
                            <p class="mt-0.5 truncate text-xs text-slate-400" @if($host) dir="ltr" style="text-align: start" @endif>{{ $secondary }}</p>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if($company->type)
                            <x-badge>{{ $company->type->label() }}</x-badge>
                        @else
                            <span class="text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <x-public.status-badge :status="$company->status" />
                    </td>
                    <td class="px-4 py-3 tabular-nums text-slate-600">{{ $linkedRatings }}</td>
                    <td class="px-4 py-3">
                        <x-admin.score-badge :score="$rowAverage" />
                    </td>
                    <td class="px-4 py-3 text-slate-500">
                        <span title="{{ $company->created_at->format('Y-m-d H:i') }}">{{ $company->created_at->diffForHumans() }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <x-admin.moderation-actions :record="$company" type="company" :confirm-reject="$confirmReject" class="justify-end">
                            <a href="{{ route('admin.companies.show', $company) }}" wire:navigate
                                class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-100 hover:text-slate-900">
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                عرض
                            </a>
                        </x-admin.moderation-actions>
                    </td>
                </tr>
            @endforeach
        </x-admin.table>

        <x-admin.pagination :paginator="$this->companies" />
    @endif
</div>
