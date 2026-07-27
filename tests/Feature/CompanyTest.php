<?php

use App\Models\Company;
use App\Models\Rating;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

test('homepage renders the footer blurb and no nav link to companies', function () {
    $response = $this->get(route('companies.index'));

    $response->assertOk();
    $response->assertSee('منصّة مستقلة لتقييم جهات التدريب التعاوني والصيفي، بتجارب حقيقية من المتدربين أنفسهم.');
    $response->assertSee('صُنع بحب لمجتمع المتدربين');
    $response->assertDontSee('rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-100', false);
});

test('homepage renders the dark mode toggle and the FOUC-guard script', function () {
    $response = $this->get(route('companies.index'));

    $response->assertOk();
    $response->assertSee('تبديل المظهر');
    $response->assertSee('data-navigate-once', false);
    $response->assertSee("localStorage.getItem('theme')", false);
    $response->assertSee("document.documentElement.classList.toggle('dark', isDark);", false);
    $response->assertDontSee('fluxAppearance', false);
    $response->assertDontSee('rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-100', false);
});

test('homepage displays approved companies', function () {
    Company::create(['name' => 'شركة معتمدة', 'status' => 'approved']);
    Company::create(['name' => 'شركة معلقة', 'status' => 'pending']);

    $response = $this->get(route('companies.index'));

    $response->assertOk();
    $response->assertSee('شركة معتمدة');
    $response->assertDontSee('شركة معلقة');
});

test('ratings_count and average_rating reuse eager-loaded columns instead of querying per company', function () {
    foreach (range(1, 9) as $i) {
        $company = Company::create(['name' => "شركة {$i}", 'status' => 'approved']);

        Rating::create([
            'company_id' => $company->id,
            'role_title' => 'مبرمج',
            'duration_months' => 3,
            'modality' => 'onsite',
            'rating_learning' => 4,
            'rating_mentorship' => 4,
            'rating_real_work' => 4,
            'rating_team_environment' => 4,
            'rating_organization' => 4,
        ]);
    }

    // Warm the request-independent caches (public stats, latest review ids)
    // first so the request under test isn't the one that populates them —
    // otherwise a cache miss would add queries unrelated to the thing this
    // test guards against.
    $this->get(route('companies.index'))->assertOk();

    DB::enableQueryLog();
    $this->get(route('companies.index'))->assertOk();
    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    // `companyResults()` eager-loads both via `withCount`/`withAvg`. If the
    // model's accessors ever stop preferring that eager-loaded data, each of
    // the 9 rendered cards fires its own extra `avg`/`count` query here —
    // this fails whether that regresses to 1 extra query per company or 2.
    $perCompanyAggregateQueries = collect($queries)->filter(
        fn (array $query): bool => str_contains($query['query'], 'from "ratings" where "ratings"."company_id" = ?')
    )->count();

    expect($perCompanyAggregateQueries)->toBe(0);
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
    $response->assertSee('bg-amber-50 text-amber-700 ring-amber-600/20', false);
    $response->assertSee('bg-emerald-600', false);
    $response->assertSee('bg-rose-400', false);
});

test('score visuals reflect the rating value: high ratings render emerald, low ratings render rose', function () {
    $company = Company::create(['name' => 'شركة تجريبية', 'status' => 'approved']);

    Rating::create([
        'company_id' => $company->id,
        'role_title' => 'مبرمج ممتاز',
        'duration_months' => 3,
        'modality' => 'onsite',
        'rating_learning' => 5,
        'rating_mentorship' => 5,
        'rating_real_work' => 5,
        'rating_team_environment' => 5,
        'rating_organization' => 5,
        'review_text' => 'تجربة رائعة',
    ]);

    Rating::create([
        'company_id' => $company->id,
        'role_title' => 'متدرب غير راضٍ',
        'duration_months' => 3,
        'modality' => 'onsite',
        'rating_learning' => 1,
        'rating_mentorship' => 1,
        'rating_real_work' => 1,
        'rating_team_environment' => 1,
        'rating_organization' => 1,
        'review_text' => 'تجربة سيئة',
    ]);

    $response = $this->get(route('companies.show', $company));

    $response->assertOk();
    // High overall score (5.0) renders the emerald tier for the score badge and bars.
    $response->assertSee('bg-emerald-50 text-emerald-700 ring-emerald-600/15', false);
    $response->assertSee('bg-emerald-600', false);
    // Low overall score (1.0) renders the muted rose tier for the score badge and bars.
    $response->assertSee('bg-rose-50 text-rose-600 ring-rose-600/15', false);
    $response->assertSee('bg-rose-400', false);
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

test('default sort orders companies by highest average rating', function () {
    $low = Company::create(['name' => 'شركة منخفضة', 'status' => 'approved']);
    $high = Company::create(['name' => 'شركة عالية', 'status' => 'approved']);
    $mid = Company::create(['name' => 'شركة متوسطة', 'status' => 'approved']);

    foreach ([$low->id => 1, $high->id => 5, $mid->id => 3] as $companyId => $score) {
        Rating::create([
            'company_id' => $companyId,
            'role_title' => 'مبرمج',
            'duration_months' => 3,
            'modality' => 'onsite',
            'rating_learning' => $score,
            'rating_mentorship' => $score,
            'rating_real_work' => $score,
            'rating_team_environment' => $score,
            'rating_organization' => $score,
        ]);
    }

    $ids = Livewire::test('pages::companies.index')
        ->assertSet('sort', 'highest_rated')
        ->instance()->companies->pluck('id')->all();

    expect($ids)->toBe([$high->id, $mid->id, $low->id]);
});

test('most_rated sort orders companies by ratings count descending', function () {
    $few = Company::create(['name' => 'شركة قليلة', 'status' => 'approved']);
    $many = Company::create(['name' => 'شركة كثيرة', 'status' => 'approved']);

    // Only one rating but the highest score — proves count beats average.
    Rating::create([
        'company_id' => $few->id,
        'role_title' => 'مبرمج',
        'duration_months' => 3,
        'modality' => 'onsite',
        'rating_learning' => 5,
        'rating_mentorship' => 5,
        'rating_real_work' => 5,
        'rating_team_environment' => 5,
        'rating_organization' => 5,
    ]);

    for ($i = 0; $i < 3; $i++) {
        Rating::create([
            'company_id' => $many->id,
            'role_title' => 'مبرمج',
            'duration_months' => 3,
            'modality' => 'onsite',
            'rating_learning' => 1,
            'rating_mentorship' => 1,
            'rating_real_work' => 1,
            'rating_team_environment' => 1,
            'rating_organization' => 1,
        ]);
    }

    $ids = Livewire::test('pages::companies.index')
        ->set('sort', 'most_rated')
        ->instance()->companies->pluck('id')->all();

    expect($ids)->toBe([$many->id, $few->id]);
});

test('most_recently_rated sort orders companies by latest rating timestamp descending', function () {
    $companyOld = Company::create(['name' => 'شركة قديمة', 'status' => 'approved']);
    $companyMid = Company::create(['name' => 'شركة وسطى', 'status' => 'approved']);
    $companyNew = Company::create(['name' => 'شركة جديدة', 'status' => 'approved']);

    $payload = [
        'role_title' => 'مبرمج',
        'duration_months' => 3,
        'modality' => 'onsite',
        'rating_learning' => 3,
        'rating_mentorship' => 3,
        'rating_real_work' => 3,
        'rating_team_environment' => 3,
        'rating_organization' => 3,
    ];

    Carbon\Carbon::setTestNow('2024-01-01 12:00:00');
    Rating::create(['company_id' => $companyOld->id] + $payload);

    Carbon\Carbon::setTestNow('2024-06-01 12:00:00');
    Rating::create(['company_id' => $companyMid->id] + $payload);

    Carbon\Carbon::setTestNow('2024-12-01 12:00:00');
    Rating::create(['company_id' => $companyNew->id] + $payload);

    Carbon\Carbon::setTestNow();

    $ids = Livewire::test('pages::companies.index')
        ->set('sort', 'most_recently_rated')
        ->instance()->companies->pluck('id')->all();

    expect($ids)->toBe([$companyNew->id, $companyMid->id, $companyOld->id]);
});

test('changing sort resets pagination', function () {
    for ($i = 1; $i <= 15; $i++) {
        Company::create(['name' => "شركة رقم {$i}", 'status' => 'approved']);
    }

    Livewire::test('pages::companies.index')
        ->call('loadMore')
        ->assertSet('perPage', 24)
        ->set('sort', 'most_rated')
        ->assertSet('perPage', 12);
});

test('invalid sort value falls back to highest_rated', function () {
    Livewire::test('pages::companies.index')
        ->set('sort', 'bogus')
        ->assertSet('sort', 'highest_rated');
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

test('favicon_url builds a google s2 favicon url from the website host', function () {
    $company = Company::create([
        'name' => 'شركة تجريبية',
        'status' => 'approved',
        'website' => 'https://www.example.com/careers',
    ]);

    expect($company->favicon_url)->toBe('https://www.google.com/s2/favicons?domain=www.example.com&sz=128');
});

test('favicon_url falls back to the raw website string when it has no parseable host', function () {
    $company = Company::create([
        'name' => 'شركة تجريبية',
        'status' => 'approved',
        'website' => 'example.com',
    ]);

    expect($company->favicon_url)->toBe('https://www.google.com/s2/favicons?domain=example.com&sz=128');
});

test('favicon_url is null when the company has no website', function () {
    $company = Company::create([
        'name' => 'شركة تجريبية',
        'status' => 'approved',
    ]);

    expect($company->favicon_url)->toBeNull();
});

test('initial returns the first character of an arabic company name', function () {
    $company = Company::create([
        'name' => 'شركة أرامكو',
        'status' => 'approved',
    ]);

    expect($company->initial)->toBe('ش');
});

test('initial trims whitespace before taking the first character', function () {
    $company = Company::create([
        'name' => '  Aramco',
        'status' => 'approved',
    ]);

    expect($company->initial)->toBe('A');
});

test('the avatar hides the initial behind a logo, and keeps it without one', function () {
    // A logo with a transparent background used to show the initial through it,
    // because both layers rendered at once. Only one may be visible; the letter
    // stays in the markup so the img's onerror can reveal it again.
    $withLogo = Company::create([
        'name' => 'شركة تجريبية',
        'status' => 'approved',
        'website' => 'https://example.com',
    ]);

    $withoutLogo = Company::create(['name' => 'مؤسسة النخبة', 'status' => 'approved']);

    $render = fn (Company $company): string => Blade::render(
        '<x-public.company-avatar :company="$company" />',
        ['company' => $company],
    );

    expect($render($withLogo))
        ->toContain('<img')
        ->toMatch('/<span[^>]*\bhidden\b[^>]*>\s*ش/u');

    expect($render($withoutLogo))
        ->not->toContain('<img')
        ->toMatch('/<span[^>]*>\s*م/u')
        ->not->toMatch('/<span[^>]*\bhidden\b/u');
});

test('homepage hero displays heading, subline, and live stats', function () {
    Company::create(['name' => 'شركة أولى', 'status' => 'approved']);
    $company = Company::create(['name' => 'شركة ثانية', 'status' => 'approved']);
    Company::create(['name' => 'شركة معلقة', 'status' => 'pending']);

    Rating::create([
        'company_id' => $company->id,
        'role_title' => 'مبرمج',
        'duration_months' => 3,
        'modality' => 'onsite',
        'rating_learning' => 5,
        'rating_mentorship' => 4,
        'rating_real_work' => 3,
        'rating_team_environment' => 4,
        'rating_organization' => 4,
    ]);

    $response = $this->get(route('companies.index'));

    $response->assertOk();
    $response->assertSee('جهات التدريب');
    $response->assertSee('تقييمات من متدربين سبقوك تساعدك في اختيار جهتك.');

    $stats = Livewire::test('pages::companies.index')->instance()->stats;

    expect($stats)->toBe(['ratings' => 1, 'companies' => 2]);
});

test('homepage shows the latest reviews strip with review content', function () {
    $company = Company::create(['name' => 'شركة تجريبية', 'status' => 'approved']);

    Rating::create([
        'company_id' => $company->id,
        'role_title' => 'مبرمج',
        'duration_months' => 3,
        'modality' => 'onsite',
        'rating_learning' => 5,
        'rating_mentorship' => 4,
        'rating_real_work' => 3,
        'rating_team_environment' => 4,
        'rating_organization' => 4,
        'review_text' => 'تجربة رائعة في القسم التقني وتعلمت الكثير من الفريق',
    ]);

    $response = $this->get(route('companies.index'));

    $response->assertOk();
    $response->assertSee('أحدث التقييمات');
    $response->assertSee('تجربة رائعة في القسم التقني وتعلمت الكثير من الفريق');
    $response->assertSee('مبرمج');
});

test('latest reviews strip is hidden when there are no reviews yet', function () {
    Company::create(['name' => 'شركة تجريبية', 'status' => 'approved']);

    $response = $this->get(route('companies.index'));

    $response->assertOk();
    $response->assertDontSee('أحدث التقييمات');
});

test('company card shows a first-to-rate call to action when it has no ratings', function () {
    Company::create(['name' => 'شركة بدون تقييمات', 'status' => 'approved']);

    $response = $this->get(route('companies.index'));

    $response->assertOk();
    $response->assertSee('كن أول من يقيّم');
});

test('company card shows a quote and recency for a company with an approved rating', function () {
    $company = Company::create(['name' => 'شركة تجريبية', 'status' => 'approved']);

    Rating::create([
        'company_id' => $company->id,
        'role_title' => 'مبرمج',
        'duration_months' => 3,
        'modality' => 'onsite',
        'rating_learning' => 5,
        'rating_mentorship' => 4,
        'rating_real_work' => 3,
        'rating_team_environment' => 4,
        'rating_organization' => 4,
        'review_text' => 'تجربة تدريب ممتازة جدا في هذه الشركة',
    ]);

    $response = $this->get(route('companies.index'));

    $response->assertOk();
    $response->assertSee('تجربة تدريب ممتازة جدا في هذه الشركة');
    $response->assertSee('آخر تقييم');
    $response->assertDontSee('كن أول من يقيّم');
});

test('latestApprovedRating returns the most recent approved rating and ignores non-approved ones', function () {
    $company = Company::create(['name' => 'شركة تجريبية', 'status' => 'approved']);

    Carbon\Carbon::setTestNow('2024-01-01 12:00:00');
    Rating::create([
        'company_id' => $company->id,
        'role_title' => 'قديم',
        'duration_months' => 3,
        'modality' => 'onsite',
        'rating_learning' => 3,
        'rating_mentorship' => 3,
        'rating_real_work' => 3,
        'rating_team_environment' => 3,
        'rating_organization' => 3,
    ]);

    Carbon\Carbon::setTestNow('2024-06-01 12:00:00');
    $latest = Rating::create([
        'company_id' => $company->id,
        'role_title' => 'جديد',
        'duration_months' => 3,
        'modality' => 'onsite',
        'rating_learning' => 3,
        'rating_mentorship' => 3,
        'rating_real_work' => 3,
        'rating_team_environment' => 3,
        'rating_organization' => 3,
    ]);

    Carbon\Carbon::setTestNow('2024-12-01 12:00:00');
    Rating::create([
        'company_id' => $company->id,
        'role_title' => 'مرفوض لاحقاً لكنه الأحدث',
        'duration_months' => 3,
        'modality' => 'onsite',
        'rating_learning' => 3,
        'rating_mentorship' => 3,
        'rating_real_work' => 3,
        'rating_team_environment' => 3,
        'rating_organization' => 3,
        'status' => 'rejected',
    ]);

    Carbon\Carbon::setTestNow();

    expect($company->fresh()->latestApprovedRating->id)->toBe($latest->id);
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

test('the latest-reviews cache holds plain ids, never hydrated models', function () {
    $company = Company::create(['name' => 'شركة تجريبية', 'status' => 'approved']);
    Rating::create([
        'company_id' => $company->id,
        'role_title' => 'مبرمج',
        'duration_months' => 3,
        'modality' => 'onsite',
        'rating_learning' => 5,
        'rating_mentorship' => 4,
        'rating_real_work' => 4,
        'rating_team_environment' => 4,
        'rating_organization' => 4,
        'review_text' => 'تجربة ممتازة',
    ]);

    // Caching Eloquent models serializes them into the store, and every later
    // request 500s unserializing them. Two loads in a row proves the round trip.
    $this->get(route('companies.index'))->assertOk();
    $this->get(route('companies.index'))->assertOk()->assertSee('تجربة ممتازة');

    expect(Cache::get('public-latest-review-ids'))->toBeArray()->each->toBeInt();
});
