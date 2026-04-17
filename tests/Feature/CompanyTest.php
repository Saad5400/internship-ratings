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
        'rating_learning' => 5,
        'rating_mentorship' => 4,
        'rating_real_work' => 2,
        'rating_team_environment' => 3,
        'rating_organization' => 4,
        'review_text' => 'تجربة ممتازة في البرمجة',
    ]);

    $response = $this->get(route('companies.show', $company));

    $response->assertOk();
    $response->assertSee('شركة تجريبية');
    $response->assertSee('مبرمج');
    $response->assertSee('تجربة ممتازة في البرمجة');
    $response->assertSee('bg-slate-100 text-slate-700 ring-slate-300', false);
    $response->assertSee('bg-sky-600', false);
    $response->assertSee('bg-slate-300', false);
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

test('company detail loadMore reveals more ratings', function () {
    $company = Company::create(['name' => 'شركة تجريبية', 'status' => 'approved']);

    for ($i = 1; $i <= 15; $i++) {
        Rating::create([
            'company_id' => $company->id,
            'role_title' => "دور رقم {$i}",
            'duration_months' => 3,
            'modality' => 'onsite',
            'rating_learning' => 5,
            'rating_mentorship' => 4,
            'rating_real_work' => 3,
            'rating_team_environment' => 3,
            'rating_organization' => 4,
            'review_text' => "تجربة {$i}",
        ]);
    }

    Livewire::test('pages::companies.show', ['company' => $company])
        ->assertSet('perPage', 10)
        ->call('loadMore')
        ->assertSet('perPage', 20);
});

test('company detail loadMore does nothing when no more ratings', function () {
    $company = Company::create(['name' => 'شركة تجريبية', 'status' => 'approved']);

    for ($i = 1; $i <= 3; $i++) {
        Rating::create([
            'company_id' => $company->id,
            'role_title' => "دور رقم {$i}",
            'duration_months' => 3,
            'modality' => 'onsite',
            'rating_learning' => 5,
            'rating_mentorship' => 4,
            'rating_real_work' => 3,
            'rating_team_environment' => 3,
            'rating_organization' => 4,
            'review_text' => "تجربة {$i}",
        ]);
    }

    Livewire::test('pages::companies.show', ['company' => $company])
        ->call('loadMore')
        ->assertSet('perPage', 10);
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

test('loadMore preserves existing company order when created_at ties', function () {
    $createdAt = now();

    for ($i = 1; $i <= 15; $i++) {
        $company = Company::create(['name' => "شركة رقم {$i}", 'status' => 'approved']);
        $company->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->save();
    }

    // All 15 share ratings_count=0 and created_at. The id desc tiebreaker yields
    // ids [15..4] on the first page, and that prefix must not reshuffle after loadMore.
    $component = Livewire::test('pages::companies.index');

    $firstPageIds = $component->get('companies')->pluck('id')->all();
    expect($firstPageIds)->toEqual(range(15, 4));

    $component->call('loadMore');

    $afterLoadIds = $component->get('companies')->pluck('id')->all();
    expect($afterLoadIds)->toEqual(range(15, 1));
    expect(array_slice($afterLoadIds, 0, 12))->toEqual($firstPageIds);
});

test('loadMore preserves existing rating order when created_at ties', function () {
    $company = Company::create(['name' => 'شركة تجريبية', 'status' => 'approved']);
    $createdAt = now();

    for ($i = 1; $i <= 15; $i++) {
        $rating = Rating::create([
            'company_id' => $company->id,
            'role_title' => "دور رقم {$i}",
            'duration_months' => 3,
            'modality' => 'onsite',
            'rating_learning' => 5,
            'rating_mentorship' => 4,
            'rating_real_work' => 3,
            'rating_team_environment' => 3,
            'rating_organization' => 4,
            'review_text' => "تجربة {$i}",
        ]);
        $rating->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->save();
    }

    $component = Livewire::test('pages::companies.show', ['company' => $company]);

    $firstPageIds = $component->get('ratings')->pluck('id')->all();
    expect($firstPageIds)->toEqual(range(15, 6));

    $component->call('loadMore');

    $afterLoadIds = $component->get('ratings')->pluck('id')->all();
    expect($afterLoadIds)->toEqual(range(15, 1));
    expect(array_slice($afterLoadIds, 0, 10))->toEqual($firstPageIds);
});
