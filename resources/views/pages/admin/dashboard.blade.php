<?php

use App\Livewire\Concerns\ModeratesRecords;
use App\Models\Company;
use App\Models\Rating;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/*
 * The panel's front door is the moderation queue itself: everything pending
 * is reviewable and actionable right here, oldest first, with stats demoted
 * below the queue. Clearing this queue is the admin's daily job.
 */
new #[Layout('layouts.admin')] #[Title('المراجعة')] class extends Component {
    use ModeratesRecords;

    public int $ratingsLimit = 5;

    protected int $ratingsPageSize = 5;

    public function loadMoreRatings(): void
    {
        $this->ratingsLimit += $this->ratingsPageSize;
        unset($this->pendingRatings);
    }

    protected function afterModeration(): void
    {
        unset($this->pendingRatings, $this->pendingCompanies, $this->pendingRatingsTotal, $this->stats);
    }

    #[Computed]
    public function pendingCompanies()
    {
        return Company::pending()->withCount('ratings as ratings_total_count')->oldest()->get();
    }

    #[Computed]
    public function pendingRatings()
    {
        return Rating::pending()->with('company')->oldest()->take($this->ratingsLimit)->get();
    }

    #[Computed]
    public function pendingRatingsTotal(): int
    {
        return Rating::pending()->count();
    }

    /**
     * @return array<int, array{label: string, value: string, description: ?string, color: string, icon: string}>
     */
    #[Computed]
    public function stats(): array
    {
        $totalCompanies = Company::count();
        $approvedCompanies = Company::approved()->count();
        $totalRatings = Rating::count();
        $averageRating = Rating::avg('overall_rating');
        $averageStipend = Rating::where('stipend_sar', '>', 0)->avg('stipend_sar');
        $jobOfferRate = $totalRatings > 0
            ? (int) round(Rating::where('job_offer', true)->count() / $totalRatings * 100)
            : 0;

        return [
            [
                'label' => 'إجمالي التقييمات',
                'value' => (string) $totalRatings,
                'description' => $averageRating !== null ? 'متوسط التقييم: '.number_format((float) $averageRating, 1).' / 5' : null,
                'color' => 'primary',
                'icon' => 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.196-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.783-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z',
            ],
            [
                'label' => 'جهات موافق عليها',
                'value' => (string) $approvedCompanies,
                'description' => $totalCompanies > 0 ? round($approvedCompanies / $totalCompanies * 100).'% من إجمالي '.trans_choice('counts.companies', $totalCompanies) : null,
                'color' => 'success',
                'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
            ],
            [
                'label' => 'متوسط المكافأة',
                'value' => $averageStipend !== null ? number_format((float) $averageStipend).' ر.س' : 'غير متوفر',
                'description' => 'شهرياً، للتقييمات المدفوعة',
                'color' => 'info',
                'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
            ],
            [
                'label' => 'نسبة عروض العمل',
                'value' => $jobOfferRate.'%',
                'description' => 'من التقييمات انتهت بعرض عمل',
                'color' => $jobOfferRate >= 50 ? 'success' : 'warning',
                'icon' => 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
            ],
        ];
    }

    #[Computed]
    public function topCompanies()
    {
        return Company::approved()
            ->has('ratings')
            ->withCount('ratings')
            ->withAvg('ratings', 'overall_rating')
            ->orderByDesc('ratings_avg_overall_rating')
            ->take(5)
            ->get();
    }

    /**
     * @return array<int, int> Count of ratings per rounded score bucket 1..5.
     */
    #[Computed]
    public function ratingsDistribution(): array
    {
        $buckets = Rating::query()
            ->whereNotNull('overall_rating')
            ->selectRaw('ROUND(overall_rating) as bucket, COUNT(*) as total')
            ->groupBy('bucket')
            ->pluck('total', 'bucket');

        return collect(range(1, 5))
            ->mapWithKeys(fn (int $score) => [$score => (int) ($buckets[$score] ?? 0)])
            ->all();
    }
}; ?>

<div class="space-y-10">
    @php
        $pendingTotal = $this->pendingRatingsTotal + $this->pendingCompanies->count();
    @endphp

    <x-admin.page-header
        title="المراجعة"
        :subtitle="$pendingTotal > 0
            ? 'بانتظارك '.trans_choice('counts.ratings', $this->pendingRatingsTotal).' و'.trans_choice('counts.companies', $this->pendingCompanies->count())
            : 'لا يوجد شيء بانتظار المراجعة'"
    />

    @if($pendingTotal === 0)
        <x-admin.empty-state
            icon="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
            message="كل شيء تمت مراجعته 🎉 التقييمات والجهات الجديدة ستظهر هنا فور وصولها"
        >
            <a href="{{ route('home') }}" target="_blank" rel="noopener" class="text-sm font-medium text-blue-500 transition-colors hover:text-blue-600">
                عرض الموقع →
            </a>
        </x-admin.empty-state>
    @endif

    @if($this->pendingCompanies->isNotEmpty())
        <section aria-labelledby="pending-companies-heading" class="space-y-4">
            <h2 id="pending-companies-heading" class="flex items-center gap-2 text-lg font-bold text-slate-900">
                جهات بانتظار المراجعة
                <x-badge color="warning">{{ $this->pendingCompanies->count() }}</x-badge>
            </h2>
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                @foreach($this->pendingCompanies as $company)
                    <div wire:key="pending-company-{{ $company->id }}">
                        <x-admin.company-review-card :company="$company" />
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if($this->pendingRatings->isNotEmpty())
        <section aria-labelledby="pending-ratings-heading" class="space-y-4">
            <h2 id="pending-ratings-heading" class="flex items-center gap-2 text-lg font-bold text-slate-900">
                تقييمات بانتظار المراجعة
                <x-badge color="warning">{{ $this->pendingRatingsTotal }}</x-badge>
            </h2>
            <div class="mx-auto max-w-3xl space-y-4">
                @foreach($this->pendingRatings as $rating)
                    <div wire:key="pending-rating-{{ $rating->id }}">
                        <x-admin.rating-review-card :rating="$rating" :reassigning="$reassignRatingId === $rating->id" />
                    </div>
                @endforeach

                @if($this->pendingRatingsTotal > $this->pendingRatings->count())
                    <button
                        type="button"
                        wire:click="loadMoreRatings"
                        wire:loading.attr="disabled"
                        wire:target="loadMoreRatings"
                        class="flex w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-600 shadow-xs transition-colors hover:bg-slate-50 disabled:opacity-60"
                    >
                        <svg wire:loading wire:target="loadMoreRatings" class="size-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                        عرض المزيد ({{ $this->pendingRatingsTotal - $this->pendingRatings->count() }} متبقٍ)
                    </button>
                @endif
            </div>
        </section>
    @endif

    <section aria-labelledby="stats-heading" class="space-y-4">
        <h2 id="stats-heading" class="text-lg font-bold text-slate-900">الإحصائيات</h2>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach($this->stats as $stat)
                <x-admin.stat-card
                    :label="$stat['label']"
                    :value="$stat['value']"
                    :description="$stat['description']"
                    :color="$stat['color']"
                    :icon="$stat['icon']"
                />
            @endforeach
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            {{-- Ratings distribution as pure-CSS bars --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs">
                <h3 class="font-semibold text-slate-900">توزيع التقييمات</h3>
                <p class="mt-0.5 text-xs text-slate-400">عدد التقييمات حسب الدرجة</p>
                @php
                    $distribution = $this->ratingsDistribution;
                    $maxBucket = max(1, max($distribution));
                    $bucketStyles = [
                        1 => 'bg-red-400',
                        2 => 'bg-orange-400',
                        3 => 'bg-amber-400',
                        4 => 'bg-lime-500',
                        5 => 'bg-green-500',
                    ];
                @endphp
                <div class="mt-4 space-y-2.5">
                    @foreach([5, 4, 3, 2, 1] as $score)
                        <div class="flex items-center gap-3">
                            <span class="flex w-5 shrink-0 items-center gap-0.5 text-xs font-semibold tabular-nums text-slate-600">{{ $score }}</span>
                            <div class="h-2.5 flex-1 overflow-hidden rounded-full bg-slate-100" role="img" aria-label="{{ trans_choice('counts.ratings', $distribution[$score]) }} بدرجة {{ $score }}">
                                <div class="h-full rounded-full {{ $bucketStyles[$score] }}" style="width: {{ round($distribution[$score] / $maxBucket * 100) }}%"></div>
                            </div>
                            <span class="w-8 shrink-0 text-end text-xs tabular-nums text-slate-500">{{ $distribution[$score] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Top rated companies --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs">
                <h3 class="font-semibold text-slate-900">أفضل الجهات تقييماً</h3>
                <p class="mt-0.5 text-xs text-slate-400">حسب متوسط التقييمات الموافق عليها</p>
                @if($this->topCompanies->isEmpty())
                    <p class="mt-4 text-sm text-slate-400">لا توجد جهات مقيّمة بعد</p>
                @else
                    <ol class="mt-4 divide-y divide-slate-100">
                        @foreach($this->topCompanies as $company)
                            <li class="flex items-center justify-between gap-3 py-2.5">
                                <div class="flex min-w-0 items-center gap-3">
                                    <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-semibold text-slate-500">{{ $loop->iteration }}</span>
                                    <span class="truncate text-sm font-medium text-slate-900">{{ $company->name }}</span>
                                </div>
                                <div class="flex shrink-0 items-center gap-2">
                                    <span class="text-xs text-slate-400">{{ trans_choice('counts.ratings', $company->ratings_count) }}</span>
                                    <x-admin.score-badge :score="$company->ratings_avg_overall_rating" />
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </div>
        </div>
    </section>
</div>
