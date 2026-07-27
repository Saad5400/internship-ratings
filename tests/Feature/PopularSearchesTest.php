<?php

use App\Models\Company;
use App\Models\PopularSearch;
use App\Support\PopularSearches;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

function popularSearchCompany(string $name): Company
{
    return Company::create(['name' => $name, 'status' => 'approved', 'type' => 'private']);
}

function recordPopularSearch(string $term, int $times = 1): void
{
    for ($i = 0; $i < $times; $i++) {
        PopularSearches::record($term);
    }
}

it('increments an existing term instead of appending a row', function () {
    recordPopularSearch('بنك الانماء', 3);

    expect(PopularSearch::query()->count())->toBe(1)
        ->and(PopularSearch::query()->first()->search_count)->toBe(3);
});

it('counts spelling variants of the same Arabic term together', function () {
    PopularSearches::record('الإنماء');
    PopularSearches::record('الانماء');

    expect(PopularSearch::query()->count())->toBe(1);

    $row = PopularSearch::query()->first();

    // Arabic::normalize unifies the alef and drops the bare hamza.
    expect($row->term)->toBe('الانما')
        ->and($row->search_count)->toBe(2)
        // The first spelling seen is what visitors are shown back.
        ->and($row->display_term)->toBe('الإنماء');
});

it('ignores empty and too-short terms', function () {
    PopularSearches::record(null);
    PopularSearches::record('');
    PopularSearches::record('   ');
    PopularSearches::record('ال');

    expect(PopularSearch::query()->count())->toBe(0);
});

it('ignores a pasted paragraph', function () {
    PopularSearches::record(str_repeat('ا', PopularSearches::MAX_TERM_LENGTH + 1));

    expect(PopularSearch::query()->count())->toBe(0);
});

it('stores no user identifiers and no fine-grained timestamp', function () {
    $this->travelTo(now()->startOfHour()->addMinutes(37));

    recordPopularSearch('بنك الانماء');

    $columns = Schema::getColumnListing('popular_searches');

    expect($columns)->toBe(['id', 'term', 'display_term', 'search_count', 'last_searched_at'])
        ->and(PopularSearch::query()->first()->last_searched_at->minute)->toBe(0);
});

it('hides terms that too few people searched for', function () {
    popularSearchCompany('بنك الانماء');

    recordPopularSearch('الانماء', PopularSearches::MIN_COUNT - 1);

    expect(PopularSearches::top())->toBe([]);

    recordPopularSearch('الانماء');
    PopularSearches::flush();

    expect(PopularSearches::top())->toBe(['الانماء']);
});

it('hides terms that lead to no company', function () {
    popularSearchCompany('بنك الانماء');

    recordPopularSearch('الانماء', PopularSearches::MIN_COUNT);
    recordPopularSearch('كلمة نابيه', PopularSearches::MIN_COUNT * 10);

    expect(PopularSearches::top())->toBe(['الانماء']);
});

it('hides terms nobody has searched for in a long while', function () {
    popularSearchCompany('بنك الانماء');

    $this->travelTo(now()->subDays(PopularSearches::RECENT_DAYS + 1));
    recordPopularSearch('الانماء', PopularSearches::MIN_COUNT);
    $this->travelBack();

    expect(PopularSearches::top())->toBe([]);
});

it('returns the most popular terms first and respects the limit', function () {
    popularSearchCompany('بنك الانماء');
    popularSearchCompany('شركة ارامكو');
    popularSearchCompany('الاتصالات السعودية');

    recordPopularSearch('ارامكو', PopularSearches::MIN_COUNT + 10);
    recordPopularSearch('الانماء', PopularSearches::MIN_COUNT + 5);
    recordPopularSearch('الاتصالات', PopularSearches::MIN_COUNT);

    expect(PopularSearches::top())->toBe(['ارامكو', 'الانماء', 'الاتصالات'])
        ->and(PopularSearches::top(2))->toBe(['ارامكو', 'الانماء'])
        ->and(PopularSearches::top(0))->toBe([]);
});

it('caches the top terms', function () {
    popularSearchCompany('بنك الانماء');
    popularSearchCompany('شركة ارامكو');

    recordPopularSearch('الانماء', PopularSearches::MIN_COUNT);

    expect(PopularSearches::top())->toBe(['الانماء']);

    recordPopularSearch('ارامكو', PopularSearches::MIN_COUNT * 5);

    expect(PopularSearches::top())->toBe(['الانماء']);

    PopularSearches::flush();

    expect(PopularSearches::top())->toBe(['ارامكو', 'الانماء']);
});

it('never lets a recording failure break search', function () {
    Exceptions::fake();

    Schema::drop('popular_searches');

    PopularSearches::record('الانماء');

    Exceptions::assertReportedCount(1);
});

test('suggestion chips survive the table not existing yet', function () {
    // A release can reach traffic before its migration lands, and start.sh runs
    // `migrate --force || true` — so a failed migration boots the app anyway.
    // The public catalogue must not 500 because a decoration could not load.
    Schema::drop('popular_searches');

    expect(PopularSearches::top())->toBe([]);

    Livewire::test('pages::companies.index')->assertOk();
});
