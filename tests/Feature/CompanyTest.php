<?php

use App\Models\Company;
use App\Models\Rating;
use Livewire\Livewire;

test('homepage displays approved companies', function () {
    Company::create(['name' => 'شركة معتمدة', 'status' => 'approved']);
    Company::create(['name' => 'شركة معلقة', 'status' => 'pending']);

    $response = $this->get(route('companies.index'));

    $response->assertOk();
    $response->assertSee('شركة معتمدة');
    $response->assertDontSee('شركة معلقة');
});

test('company detail page shows ratings', function () {
    $company = Company::create(['name' => 'شركة تجريبية', 'status' => 'approved']);
    Rating::create([
        'company_id' => $company->id,
        'role_title' => 'مبرمج',
        'duration_months' => 3,
        'modality' => 'onsite',
        'rating_mentorship' => 4,
        'rating_learning' => 5,
        'rating_culture' => 4,
        'rating_compensation' => 3,
        'overall_rating' => 4,
        'recommendation' => 'yes',
        'review_text' => 'تجربة ممتازة في البرمجة',
    ]);

    $response = $this->get(route('companies.show', $company));

    $response->assertOk();
    $response->assertSee('شركة تجريبية');
    $response->assertSee('مبرمج');
    $response->assertSee('تجربة ممتازة في البرمجة');
});

test('unapproved company returns 404', function () {
    $company = Company::create(['name' => 'شركة معلقة', 'status' => 'pending']);

    $response = $this->get(route('companies.show', $company));

    $response->assertNotFound();
});

test('search filters companies by name', function () {
    Company::create(['name' => 'شركة أرامكو', 'status' => 'approved']);
    Company::create(['name' => 'شركة سابك', 'status' => 'approved']);

    Livewire::test('pages::companies.index')
        ->set('search', 'أرامكو')
        ->assertSee('شركة أرامكو')
        ->assertDontSee('شركة سابك');
});

test('search is fuzzy: hamza variants match plain alef', function () {
    Company::create(['name' => 'شركة ارامكو', 'status' => 'approved']); // stored without hamza
    Company::create(['name' => 'شركة سابك', 'status' => 'approved']);

    Livewire::test('pages::companies.index')
        ->set('search', 'أرامكو') // searched with hamza
        ->assertSee('شركة ارامكو')
        ->assertDontSee('شركة سابك');
});

test('search is fuzzy: plain alef matches hamza variants', function () {
    Company::create(['name' => 'شركة أرامكو', 'status' => 'approved']); // stored with hamza
    Company::create(['name' => 'شركة سابك', 'status' => 'approved']);

    Livewire::test('pages::companies.index')
        ->set('search', 'ارامكو') // searched without hamza
        ->assertSee('شركة أرامكو')
        ->assertDontSee('شركة سابك');
});

test('search is fuzzy: ta marbuta matches ha', function () {
    Company::create(['name' => 'شركة الراجحي', 'status' => 'approved']);

    Livewire::test('pages::companies.index')
        ->set('search', 'شركه الراجحي') // ta marbuta → ha
        ->assertSee('شركة الراجحي');
});

test('search ignores tashkeel', function () {
    Company::create(['name' => 'شركة أرامكو', 'status' => 'approved']);

    Livewire::test('pages::companies.index')
        ->set('search', 'أَرَامْكُو') // with diacritics
        ->assertSee('شركة أرامكو');
});

test('loadMore increases visible companies', function () {
    // 15 approved companies — first page shows 12, loadMore reveals the rest
    for ($i = 1; $i <= 15; $i++) {
        Company::create(['name' => "شركة رقم {$i}", 'status' => 'approved']);
    }

    Livewire::test('pages::companies.index')
        ->assertSet('perPage', 12)
        ->call('loadMore')
        ->assertSet('perPage', 24);
});

test('loadMore does nothing when no more results', function () {
    for ($i = 1; $i <= 5; $i++) {
        Company::create(['name' => "شركة رقم {$i}", 'status' => 'approved']);
    }

    Livewire::test('pages::companies.index')
        ->call('loadMore')
        ->assertSet('perPage', 12); // unchanged because hasMore=false
});

test('typing new search resets pagination', function () {
    for ($i = 1; $i <= 15; $i++) {
        Company::create(['name' => "شركة رقم {$i}", 'status' => 'approved']);
    }

    Livewire::test('pages::companies.index')
        ->call('loadMore')
        ->assertSet('perPage', 24)
        ->set('search', 'رقم')
        ->assertSet('perPage', 12);
});

