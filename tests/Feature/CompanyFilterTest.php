<?php

use App\Models\Company;
use App\Models\Rating;
use App\Support\CompanyFacets;
use Livewire\Livewire;

/**
 * @param  array<string, mixed>  $attributes
 */
function ratedCompany(string $name, array $attributes = [], ?string $type = 'private'): Company
{
    $company = Company::create(['name' => $name, 'status' => 'approved', 'type' => $type]);

    Rating::create(array_merge([
        'company_id' => $company->id,
        'role_title' => 'مطور',
        'city' => 'الرياض',
        'duration_months' => 3,
        'modality' => 'onsite',
        'stipend_sar' => 3000,
        'recommendation' => 'yes',
        'rating_mentorship' => 4,
        'rating_learning' => 4,
        'rating_real_work' => 4,
        'rating_team_environment' => 4,
        'rating_organization' => 4,
    ], $attributes));

    return $company;
}

test('a facet narrows the list to companies with a matching approved rating', function () {
    ratedCompany('شركة الرياض', ['city' => 'الرياض']);
    ratedCompany('شركة جدة', ['city' => 'جدة']);

    Livewire::test('pages::companies.index')
        ->set('filters', ['city' => ['جدة']])
        ->assertSee('شركة جدة')
        ->assertDontSee('شركة الرياض');
});

test('values inside one facet are OR-ed', function () {
    ratedCompany('شركة الرياض', ['city' => 'الرياض']);
    ratedCompany('شركة جدة', ['city' => 'جدة']);
    ratedCompany('شركة الدمام', ['city' => 'الدمام']);

    Livewire::test('pages::companies.index')
        ->set('filters', ['city' => ['جدة', 'الدمام']])
        ->assertSee('شركة جدة')
        ->assertSee('شركة الدمام')
        ->assertDontSee('شركة الرياض');
});

test('the none option matches ratings with no recorded value', function () {
    ratedCompany('شركة بلا مدينة', ['city' => null]);
    ratedCompany('شركة جدة', ['city' => 'جدة']);

    Livewire::test('pages::companies.index')
        ->set('filters', ['city' => [CompanyFacets::NONE]])
        ->assertSee('شركة بلا مدينة')
        ->assertDontSee('شركة جدة');
});

test('the none option can be combined with concrete values', function () {
    ratedCompany('شركة بلا مدينة', ['city' => null]);
    ratedCompany('شركة جدة', ['city' => 'جدة']);
    ratedCompany('شركة الرياض', ['city' => 'الرياض']);

    Livewire::test('pages::companies.index')
        ->set('filters', ['city' => [CompanyFacets::NONE, 'جدة']])
        ->assertSee('شركة بلا مدينة')
        ->assertSee('شركة جدة')
        ->assertDontSee('شركة الرياض');
});

test('different facets are AND-ed, and rating facets must be met by the same rating', function () {
    $split = Company::create(['name' => 'شركة مقسمة', 'status' => 'approved', 'type' => 'private']);

    // Jeddah onsite + Riyadh remote: neither rating is "Jeddah and remote".
    Rating::create([
        'company_id' => $split->id, 'role_title' => 'مطور', 'city' => 'جدة',
        'duration_months' => 3, 'modality' => 'onsite', 'recommendation' => 'yes',
        'rating_mentorship' => 4, 'rating_learning' => 4, 'rating_real_work' => 4,
        'rating_team_environment' => 4, 'rating_organization' => 4,
    ]);
    Rating::create([
        'company_id' => $split->id, 'role_title' => 'مطور', 'city' => 'الرياض',
        'duration_months' => 3, 'modality' => 'remote', 'recommendation' => 'yes',
        'rating_mentorship' => 4, 'rating_learning' => 4, 'rating_real_work' => 4,
        'rating_team_environment' => 4, 'rating_organization' => 4,
    ]);

    ratedCompany('شركة مطابقة', ['city' => 'جدة', 'modality' => 'remote']);

    Livewire::test('pages::companies.index')
        ->set('filters', ['city' => ['جدة'], 'modality' => ['remote']])
        ->assertSee('شركة مطابقة')
        ->assertDontSee('شركة مقسمة');
});

test('pending companies and unapproved ratings never leak into filtered results', function () {
    $pending = Company::create(['name' => 'شركة معلقة', 'status' => 'pending', 'type' => 'private']);
    Rating::create([
        'company_id' => $pending->id, 'role_title' => 'مطور', 'city' => 'جدة',
        'duration_months' => 3, 'modality' => 'onsite', 'recommendation' => 'yes',
        'rating_mentorship' => 4, 'rating_learning' => 4, 'rating_real_work' => 4,
        'rating_team_environment' => 4, 'rating_organization' => 4,
    ]);

    ratedCompany('شركة بتقييم معلق', ['city' => 'جدة', 'status' => 'pending']);

    $component = Livewire::test('pages::companies.index')
        ->set('filters', ['city' => ['جدة']]);

    $component->assertDontSee('شركة معلقة');
    $component->assertDontSee('شركة بتقييم معلق');

    expect(collect($component->get('facetOptions')['city'])->pluck('value'))->not->toContain('جدة');
});

test('company-level and rating-level facets combine', function () {
    ratedCompany('جهة حكومية بجدة', ['city' => 'جدة'], type: 'government');
    ratedCompany('شركة خاصة بجدة', ['city' => 'جدة'], type: 'private');

    Livewire::test('pages::companies.index')
        ->set('filters', ['city' => ['جدة'], 'type' => ['government']])
        ->assertSee('جهة حكومية بجدة')
        ->assertDontSee('شركة خاصة بجدة');
});

test('facet options carry company counts and exclude values that would return nothing', function () {
    ratedCompany('شركة جدة أ', ['city' => 'جدة', 'modality' => 'onsite']);
    ratedCompany('شركة جدة ب', ['city' => 'جدة', 'modality' => 'onsite']);
    ratedCompany('شركة الرياض', ['city' => 'الرياض', 'modality' => 'remote']);

    $options = Livewire::test('pages::companies.index')
        ->set('filters', ['modality' => ['onsite']])
        ->get('facetOptions');

    // Riyadh is remote-only, so offering it under "onsite" would dead-end.
    expect(collect($options['city'])->pluck('count', 'value')->all())
        ->toBe(['جدة' => 2]);

    // A facet's own selection never constrains its own counts, so both
    // modalities stay pickable.
    expect(collect($options['modality'])->pluck('value')->all())
        ->toBe(['onsite', 'remote']);
});

test('facet counts count companies, not ratings', function () {
    $company = ratedCompany('شركة بتقييمين', ['city' => 'جدة']);
    Rating::create([
        'company_id' => $company->id, 'role_title' => 'محلل', 'city' => 'جدة',
        'duration_months' => 3, 'modality' => 'onsite', 'recommendation' => 'yes',
        'rating_mentorship' => 4, 'rating_learning' => 4, 'rating_real_work' => 4,
        'rating_team_environment' => 4, 'rating_organization' => 4,
    ]);

    $options = Livewire::test('pages::companies.index')->get('facetOptions');

    expect(collect($options['city'])->firstWhere('value', 'جدة')['count'])->toBe(1);
});

test('search and facets narrow together', function () {
    ratedCompany('بنك الراجحي', ['city' => 'جدة']);
    ratedCompany('بنك الأهلي', ['city' => 'الرياض']);

    Livewire::test('pages::companies.index')
        ->set('search', 'بنك')
        ->set('filters', ['city' => ['جدة']])
        ->assertSee('بنك الراجحي')
        ->assertDontSee('بنك الأهلي');
});

test('toggleFilter adds then removes a value and resets paging', function () {
    ratedCompany('شركة جدة', ['city' => 'جدة']);

    $component = Livewire::test('pages::companies.index')
        ->set('perPage', 48)
        ->call('toggleFilter', 'city', 'جدة');

    expect($component->get('filters'))->toBe(['city' => ['جدة']]);
    expect($component->get('perPage'))->toBe(12);

    $component->call('toggleFilter', 'city', 'جدة');

    expect($component->get('filters'))->toBe([]);
});

test('unknown facets and junk values are rejected', function () {
    ratedCompany('شركة جدة', ['city' => 'جدة']);

    $component = Livewire::test('pages::companies.index')
        ->call('toggleFilter', 'is_admin', '1');

    expect($component->get('filters'))->toBe([]);

    expect(CompanyFacets::sanitize([
        'city' => ['جدة', '', 'جدة', 5],
        'nope' => ['x'],
    ]))->toBe(['city' => ['جدة']]);
});

test('clearFilters drops both the search term and every facet', function () {
    ratedCompany('شركة جدة', ['city' => 'جدة']);
    ratedCompany('شركة الرياض', ['city' => 'الرياض']);

    $component = Livewire::test('pages::companies.index')
        ->set('search', 'شركة')
        ->set('filters', ['city' => ['جدة']])
        ->call('clearFilters');

    expect($component->get('filters'))->toBe([]);
    expect($component->get('search'))->toBe('');
    $component->assertSee('شركة الرياض');
});

test('a facet with nothing to choose between is not rendered', function () {
    // Every company is private and onsite in Riyadh — those facets are noise.
    ratedCompany('شركة أ');
    ratedCompany('شركة ب');

    $facets = Livewire::test('pages::companies.index')->get('facets');

    expect(collect($facets)->pluck('key')->all())->toBe([]);
});

test('a facet keeps its chip while it is filtering, even with one option left', function () {
    ratedCompany('شركة جدة', ['city' => 'جدة']);
    ratedCompany('شركة الرياض', ['city' => 'الرياض']);

    $facets = Livewire::test('pages::companies.index')
        ->set('filters', ['modality' => ['onsite']])
        ->get('facets');

    expect(collect($facets)->pluck('key')->all())->toContain('modality');
});

test('the stipend facet splits on whether a stipend was recorded', function () {
    ratedCompany('شركة بمكافأة', ['stipend_sar' => 4000]);
    ratedCompany('شركة بدون مكافأة', ['stipend_sar' => null]);

    Livewire::test('pages::companies.index')
        ->set('filters', ['stipend' => ['unpaid']])
        ->assertSee('شركة بدون مكافأة')
        ->assertDontSee('شركة بمكافأة');
});

test('the duration facet buckets months', function () {
    ratedCompany('تدريب قصير', ['duration_months' => 2]);
    ratedCompany('تدريب متوسط', ['duration_months' => 4]);
    ratedCompany('تدريب طويل', ['duration_months' => 6]);

    Livewire::test('pages::companies.index')
        ->set('filters', ['duration' => ['short', 'long']])
        ->assertSee('تدريب قصير')
        ->assertSee('تدريب طويل')
        ->assertDontSee('تدريب متوسط');
});

test('facet option labels come from the owning enum', function () {
    expect(CompanyFacets::optionLabel('modality', 'remote'))->toBe('عن بُعد');
    expect(CompanyFacets::optionLabel('type', 'government'))->toBe('حكومي');
    expect(CompanyFacets::optionLabel('recommendation', 'yes'))->toBe('أنصح به');
    expect(CompanyFacets::optionLabel('city', 'جدة'))->toBe('جدة');
    expect(CompanyFacets::optionLabel('city', CompanyFacets::NONE))->toBe('غير محدد');
});

test('filters survive a round trip through the query string', function () {
    ratedCompany('شركة جدة', ['city' => 'جدة']);
    ratedCompany('شركة الرياض', ['city' => 'الرياض']);

    $response = $this->get(route('companies.index', ['f' => ['city' => ['جدة']]]));

    $response->assertOk();
    $response->assertSee('شركة جدة');
    $response->assertDontSee('شركة الرياض');
});
