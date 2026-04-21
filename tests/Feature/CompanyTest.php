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

test('loadMore preserves order of previously loaded companies when ratings_count and created_at tie', function () {
    // All companies share identical created_at AND zero ratings_count — only a
    // stable tiebreaker (id) keeps the order deterministic across loadMore.
    Carbon\Carbon::setTestNow('2024-01-01 12:00:00');

    for ($i = 1; $i <= 20; $i++) {
        Company::create(['name' => "شركة رقم {$i}", 'status' => 'approved']);
    }

    $component = Livewire::test('pages::companies.index');

    $firstPage = $component->instance()->companies->pluck('id')->all();

    $component->call('loadMore');

    $afterLoadMore = $component->instance()->companies->pluck('id')->all();

    // The first page's ids must appear in the exact same order and positions
    // at the start of the combined list after loadMore.
    expect(array_slice($afterLoadMore, 0, count($firstPage)))->toBe($firstPage);
});

test('loadMore preserves order of previously loaded ratings when created_at ties', function () {
    Carbon\Carbon::setTestNow('2024-01-01 12:00:00');

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

    $component = Livewire::test('pages::companies.show', ['company' => $company]);

    $firstPage = $component->instance()->ratings->pluck('id')->all();

    $component->call('loadMore');

    $afterLoadMore = $component->instance()->ratings->pluck('id')->all();

    expect(array_slice($afterLoadMore, 0, count($firstPage)))->toBe($firstPage);
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

test('company detail page shows reviewer academic background and application method', function () {
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
        'reviewer_name' => 'أحمد',
        'reviewer_university' => 'جامعة الملك سعود',
        'reviewer_college' => 'كلية الحاسب',
        'reviewer_major' => 'علوم الحاسب',
        'reviewer_degree' => 'bachelor',
        'application_method' => 'عبر الموقع الرسمي',
    ]);

    $response = $this->get(route('companies.show', $company));

    $response->assertOk();
    $response->assertSee('جامعة الملك سعود');
    $response->assertSee('كلية الحاسب');
    $response->assertSee('بكالوريوس');
    $response->assertSee('عبر الموقع الرسمي');
    $response->assertSee('الخلفية الأكاديمية');
    $response->assertSee('طريقة التقديم');
});

test('contact method is hidden from crawlers by default', function () {
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
        'review_text' => 'تجربة ممتازة',
        'willing_to_help' => true,
        'contact_method' => 'twitter: @secret_handle',
    ]);

    $response = $this->get(route('companies.show', $company));

    $response->assertOk();
    $response->assertSee('مستعد لمساعدة الآخرين');
    $response->assertSee('إظهار طريقة التواصل');
    $response->assertDontSee('twitter: @secret_handle');
    $response->assertDontSee('@secret_handle');
});

test('revealContact exposes the contact method after click', function () {
    $company = Company::create(['name' => 'شركة تجريبية', 'status' => 'approved']);
    $rating = Rating::create([
        'company_id' => $company->id,
        'role_title' => 'مبرمج',
        'duration_months' => 3,
        'modality' => 'onsite',
        'rating_learning' => 5,
        'rating_mentorship' => 4,
        'rating_real_work' => 2,
        'rating_team_environment' => 3,
        'rating_organization' => 4,
        'review_text' => 'تجربة ممتازة',
        'willing_to_help' => true,
        'contact_method' => 'twitter: @secret_handle',
    ]);

    Livewire::test('pages::companies.show', ['company' => $company])
        ->assertDontSee('twitter: @secret_handle')
        ->call('revealContact', $rating->id)
        ->assertSet('revealedContacts', [$rating->id])
        ->assertSee('twitter: @secret_handle');
});

test('revealContact is a no-op when rating is not willing to help', function () {
    $company = Company::create(['name' => 'شركة تجريبية', 'status' => 'approved']);
    $rating = Rating::create([
        'company_id' => $company->id,
        'role_title' => 'مبرمج',
        'duration_months' => 3,
        'modality' => 'onsite',
        'rating_learning' => 5,
        'rating_mentorship' => 4,
        'rating_real_work' => 2,
        'rating_team_environment' => 3,
        'rating_organization' => 4,
        'review_text' => 'تجربة ممتازة',
        'willing_to_help' => false,
        'contact_method' => 'twitter: @secret_handle',
    ]);

    Livewire::test('pages::companies.show', ['company' => $company])
        ->call('revealContact', $rating->id)
        ->assertSet('revealedContacts', [])
        ->assertDontSee('twitter: @secret_handle');
});

test('revealContact ignores rating ids from other companies', function () {
    $companyA = Company::create(['name' => 'شركة أ', 'status' => 'approved']);
    $companyB = Company::create(['name' => 'شركة ب', 'status' => 'approved']);

    $foreignRating = Rating::create([
        'company_id' => $companyB->id,
        'role_title' => 'مبرمج',
        'duration_months' => 3,
        'modality' => 'onsite',
        'rating_learning' => 5,
        'rating_mentorship' => 4,
        'rating_real_work' => 2,
        'rating_team_environment' => 3,
        'rating_organization' => 4,
        'review_text' => 'تجربة ممتازة',
        'willing_to_help' => true,
        'contact_method' => 'twitter: @foreign_handle',
    ]);

    Livewire::test('pages::companies.show', ['company' => $companyA])
        ->call('revealContact', $foreignRating->id)
        ->assertSet('revealedContacts', [])
        ->assertDontSee('twitter: @foreign_handle');
});
