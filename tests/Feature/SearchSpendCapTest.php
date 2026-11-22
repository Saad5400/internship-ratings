<?php

use App\Models\Company;
use App\Models\Rating;
use App\Support\Search\CompanySearch;
use App\Support\Search\Embedder;
use App\Support\Search\FakeEmbedder;
use Livewire\Livewire;

/*
 * Public search embeds every new query with a paid provider. These pin the
 * spend caps: an over-long query is cut before it is embedded, and unique
 * queries past the per-IP / global budget fall back to lexical-only search
 * instead of calling the provider.
 */

/** A fake embedder that records every text it is asked to embed. */
function recordingEmbedder(): FakeEmbedder
{
    return new class extends FakeEmbedder
    {
        /** @var list<string> */
        public array $seen = [];

        public function embed(array $texts): array
        {
            array_push($this->seen, ...array_values($texts));

            return parent::embed($texts);
        }
    };
}

beforeEach(function () {
    $this->embedder = recordingEmbedder();
    app()->instance(Embedder::class, $this->embedder);
    app()->forgetInstance(CompanySearch::class);

    $company = Company::create(['name' => 'شركة الأفق للتقنية', 'status' => 'approved', 'type' => 'private']);
    Rating::create([
        'company_id' => $company->id, 'status' => 'approved', 'role_title' => 'مطور', 'city' => 'الرياض',
        'duration_months' => 3, 'modality' => 'onsite', 'recommendation' => 'yes',
        'rating_mentorship' => 4, 'rating_learning' => 4, 'rating_real_work' => 4,
        'rating_team_environment' => 4, 'rating_organization' => 4,
    ]);

    $this->company = $company;
    $this->embedder->seen = [];
});

test('an over-long query is cut before it is embedded', function () {
    config(['search.max_query_length' => 20]);

    app(CompanySearch::class)->search(str_repeat('تقنية ', 2000));

    expect($this->embedder->seen)->toHaveCount(1)
        ->and(mb_strlen($this->embedder->seen[0]))->toBeLessThanOrEqual(20);
});

test('the search box itself is clamped', function () {
    config(['search.max_query_length' => 20]);

    Livewire::test('pages::companies.index')
        ->set('search', str_repeat('ا', 500))
        ->assertSet('search', str_repeat('ا', 20));
});

test('unique queries past the per-IP budget skip the provider but still search lexically', function () {
    config(['search.semantic.embed_per_ip_per_minute' => 3]);

    foreach (['تقنية اولى', 'تقنية ثانية', 'تقنية ثالثة', 'تقنية رابعة', 'تقنية خامسة'] as $query) {
        app()->forgetInstance(CompanySearch::class);
        app(CompanySearch::class)->search($query);
    }

    expect($this->embedder->seen)->toHaveCount(3);

    // Lexical still answers once the budget is spent.
    app()->forgetInstance(CompanySearch::class);
    expect(app(CompanySearch::class)->search('الأفق'))->toHaveKey($this->company->id)
        ->and($this->embedder->seen)->toHaveCount(3);
});

test('the global budget caps embeddings across all visitors', function () {
    config(['search.semantic.embed_global_per_hour' => 2]);

    foreach (['1.1.1.1', '2.2.2.2', '3.3.3.3'] as $i => $ip) {
        request()->server->set('REMOTE_ADDR', $ip);
        app()->forgetInstance(CompanySearch::class);
        app(CompanySearch::class)->search("تقنية رقم {$i}");
    }

    expect($this->embedder->seen)->toHaveCount(2);
});

test('a repeated query is served from cache and costs no budget', function () {
    config(['search.semantic.embed_per_ip_per_minute' => 1]);

    foreach (range(1, 3) as $_) {
        app()->forgetInstance(CompanySearch::class);
        app(CompanySearch::class)->search('تقنية متكررة');
    }

    expect($this->embedder->seen)->toHaveCount(1);
});
