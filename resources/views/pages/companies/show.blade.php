<?php

use App\Models\Company;
use App\Models\Rating;
use App\Models\RatingVote;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.public')] class extends Component {
    public Company $company;

    public int $perPage = 10;

    /** @var array<int, int> */
    public array $revealedContacts = [];

    /** @var array<int, int> */
    public array $votedRatingIds = [];

    protected int $pageSize = 10;

    public function mount(Company $company): void
    {
        abort_unless($company->status === 'approved', 404);

        $this->company = $company;

        $this->votedRatingIds = RatingVote::query()
            ->where('voter_hash', Rating::voterHash(session()->getId()))
            ->whereIn('rating_id', $this->company->ratings()->pluck('id'))
            ->pluck('rating_id')
            ->all();
    }

    public function rendering($view): void
    {
        $view->title($this->company->name);

        $description = filled($this->company->description)
            ? \Illuminate\Support\Str::limit($this->company->description, 155)
            : 'اقرأ تقييمات وتجارب المتدربين في '.$this->company->name.' للتدريب التعاوني والصيفي، وشارك تجربتك لمساعدة غيرك على اختيار جهة التدريب المناسبة.';

        $view->with('metaDescription', $description);
    }

    public function loadMore(): void
    {
        if (! $this->hasMore) {
            return;
        }

        $this->perPage += $this->pageSize;
        unset($this->ratingResults, $this->ratings, $this->hasMore);
    }

    public function revealContact(int $ratingId): void
    {
        $isRevealable = $this->company->ratings()
            ->approved()
            ->whereKey($ratingId)
            ->where('willing_to_help', true)
            ->whereNotNull('contact_method')
            ->exists();

        if (! $isRevealable) {
            return;
        }

        if (! in_array($ratingId, $this->revealedContacts, true)) {
            $this->revealedContacts[] = $ratingId;
        }
    }

    public function voteHelpful(int $ratingId): void
    {
        $isVotable = $this->company->ratings()
            ->approved()
            ->whereKey($ratingId)
            ->exists();

        if (! $isVotable) {
            return;
        }

        $voterHash = Rating::voterHash(session()->getId());

        $removed = RatingVote::query()
            ->where('rating_id', $ratingId)
            ->where('voter_hash', $voterHash)
            ->delete();

        if ($removed > 0) {
            $this->votedRatingIds = array_values(array_diff($this->votedRatingIds, [$ratingId]));
        } else {
            RatingVote::firstOrCreate([
                'rating_id' => $ratingId,
                'voter_hash' => $voterHash,
            ]);

            if (! in_array($ratingId, $this->votedRatingIds, true)) {
                $this->votedRatingIds[] = $ratingId;
            }
        }

        unset($this->ratingResults, $this->ratings);
    }

    #[Computed]
    public function ratingResults()
    {
        return $this->company->ratings()
            ->approved()
            ->withCount('votes as helpful_votes_count')
            ->latest()
            ->latest('id')
            ->take($this->perPage + 1)
            ->get();
    }

    #[Computed]
    public function ratings()
    {
        return $this->ratingResults->take($this->perPage);
    }

    #[Computed]
    public function hasMore(): bool
    {
        return $this->ratingResults->count() > $this->perPage;
    }
}; ?>

<div class="space-y-8">
    <a href="{{ route('companies.index') }}" wire:navigate class="group inline-flex items-center gap-1 text-sm font-medium text-slate-500 transition-colors hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100">
        <svg class="size-4 transition-transform group-hover:-translate-x-0.5 rtl:group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        العودة للجهات
    </a>

    <div class="rounded-2xl border border-slate-200/80 bg-white p-6 sm:p-8 shadow-xs dark:border-slate-800 dark:bg-slate-900">
        <div class="flex items-start justify-between gap-6">
            <div class="flex min-w-0 flex-1 items-start gap-4">
                <x-public.company-avatar :company="$company" size="lg" />
                <div class="min-w-0 flex-1">
                    <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900 dark:text-slate-100">{{ $company->name }}</h1>
                    @if($company->description)
                        <p class="mt-3 text-slate-600 leading-relaxed dark:text-slate-400">{{ $company->description }}</p>
                    @endif
                    @if($company->website)
                        <a href="{{ $company->website }}" target="_blank" rel="noopener"
                            class="mt-3 inline-flex items-center gap-1.5 text-sm font-medium text-blue-500 transition-colors hover:text-blue-600">
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                            {{ parse_url($company->website, PHP_URL_HOST) ?? $company->website }}
                        </a>
                    @endif
                </div>
            </div>
            @if($company->average_rating)
                <x-public.overall-score :value="$company->average_rating" />
            @endif
        </div>
        <div class="mt-6 flex items-center gap-4 border-t border-slate-100 pt-4 text-xs text-slate-400 dark:border-slate-800 dark:text-slate-500">
            <span class="inline-flex items-center gap-1.5">
                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                <x-public.count-noun :count="$company->ratings_count" noun="ratings" :duration="800" class="tabular-nums" />
            </span>
        </div>
    </div>

    <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold text-slate-900 dark:text-slate-100">التقييمات</h2>
        <a href="{{ route('ratings.create', ['company' => $company->id]) }}" wire:navigate
            class="inline-flex items-center gap-1.5 rounded-lg bg-blue-500 px-4 py-2 text-sm font-medium text-white shadow-xs transition-all hover:bg-blue-600 hover:shadow-sm active:scale-[0.98]">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            أضف تقييم
        </a>
    </div>

    @if($this->ratings->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-200 bg-white py-16 text-center dark:border-slate-800 dark:bg-slate-900">
            <svg class="mx-auto size-12 text-slate-300 dark:text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
            <p class="mt-4 text-sm text-slate-500 dark:text-slate-400">لا توجد تقييمات بعد. كن أول من يقيّم!</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($this->ratings as $rating)
                <div wire:key="rating-{{ $rating->id }}">
                    <x-public.rating-card
                        :rating="$rating"
                        :contact-revealed="in_array($rating->id, $revealedContacts, true)"
                        :voted="in_array($rating->id, $votedRatingIds, true)"
                    />
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

            <div wire:loading.block wire:target="loadMore" class="space-y-4">
                <x-public.rating-card-skeleton :count="2" />
            </div>
        @endif
    @endif

    @php
        $orgJsonLd = array_filter([
            '@type' => 'Organization',
            'name' => $company->name,
            'url' => route('companies.show', $company),
            'description' => $company->description ?: null,
            'sameAs' => $company->website ? [$company->website] : null,
            'aggregateRating' => ($company->average_rating && $company->ratings_count)
                ? array_filter([
                    '@type' => 'AggregateRating',
                    'ratingValue' => round((float) $company->average_rating, 1),
                    'reviewCount' => $company->ratings_count,
                    'bestRating' => 5,
                    'worstRating' => 1,
                ])
                : null,
        ]);

        $jsonLd = [
            '@context' => 'https://schema.org',
            '@graph' => [
                $orgJsonLd,
                [
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        [
                            '@type' => 'ListItem',
                            'position' => 1,
                            'name' => 'الجهات',
                            'item' => route('companies.index'),
                        ],
                        [
                            '@type' => 'ListItem',
                            'position' => 2,
                            'name' => $company->name,
                            'item' => route('companies.show', $company),
                        ],
                    ],
                ],
            ],
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
</div>
