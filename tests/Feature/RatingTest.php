<?php

use App\Enums\SaudiCity;
use App\Models\Company;
use App\Models\Rating;
use Livewire\Livewire;

test('rating form is accessible', function () {
    $response = $this->get(route('ratings.create'));

    $response->assertOk();
    $response->assertSee('أضف تقييم');
    $response->assertDontSee('<select wire:model="duration_months"', false);
    $response->assertSee('id="duration_months_search"', false);
    $response->assertSee('inputmode="numeric"', false);
});

test('wizard starts on step 1', function () {
    Livewire::test('pages::ratings.create')
        ->assertSet('currentStep', 1);
});

test('cannot advance past step 1 without required fields', function () {
    Livewire::test('pages::ratings.create')
        ->call('nextStep')
        ->assertHasErrors(['companyId', 'city', 'modality'])
        ->assertSet('currentStep', 1);
});

test('can advance past step 1 with all required fields filled', function () {
    $company = Company::create(['name' => 'شركة تجريبية', 'type' => 'private', 'status' => 'approved']);

    Livewire::test('pages::ratings.create')
        ->set('companyId', (string) $company->id)
        ->set('role_title', 'مهندس')
        ->set('city', SaudiCity::Riyadh->value)
        ->set('duration_months', 3)
        ->set('modality', 'onsite')
        ->call('nextStep')
        ->assertHasNoErrors()
        ->assertSet('currentStep', 2);
});

test('cannot advance past step 2 without required scores', function () {
    $company = Company::create(['name' => 'شركة تجريبية', 'type' => 'private', 'status' => 'approved']);

    Livewire::test('pages::ratings.create')
        ->set('companyId', (string) $company->id)
        ->set('role_title', 'مهندس')
        ->set('city', SaudiCity::Riyadh->value)
        ->set('duration_months', 3)
        ->set('modality', 'onsite')
        ->call('nextStep')
        ->call('nextStep')
        ->assertHasErrors(['rating_mentorship', 'rating_learning', 'rating_real_work', 'rating_team_environment', 'rating_organization'])
        ->assertSet('currentStep', 2);
});

test('can navigate backwards freely without validation', function () {
    $company = Company::create(['name' => 'شركة تجريبية', 'type' => 'private', 'status' => 'approved']);

    Livewire::test('pages::ratings.create')
        ->set('companyId', (string) $company->id)
        ->set('role_title', 'مهندس')
        ->set('city', SaudiCity::Riyadh->value)
        ->set('duration_months', 3)
        ->set('modality', 'onsite')
        ->call('nextStep')
        ->set('rating_mentorship', 4)
        ->set('rating_learning', 4)
        ->set('rating_real_work', 4)
        ->set('rating_team_environment', 4)
        ->set('rating_organization', 3)
        ->call('nextStep')
        ->assertSet('currentStep', 3)
        ->call('prevStep')
        ->assertSet('currentStep', 2)
        ->assertHasNoErrors();
});

test('recommendation defaults from the calculated overall score', function () {
    $company = Company::create(['name' => 'شركة تجريبية', 'type' => 'private', 'status' => 'approved']);

    Livewire::test('pages::ratings.create')
        ->set('companyId', (string) $company->id)
        ->set('city', SaudiCity::Riyadh->value)
        ->set('modality', 'onsite')
        ->call('nextStep')
        ->set('rating_learning', 5)
        ->set('rating_mentorship', 5)
        ->set('rating_real_work', 4)
        ->set('rating_team_environment', 4)
        ->set('rating_organization', 3)
        ->assertSet('recommendation', 'yes');
});

test('goToStep blocks forward jumps when intermediate steps are invalid', function () {
    $company = Company::create(['name' => 'شركة تجريبية', 'type' => 'private', 'status' => 'approved']);

    Livewire::test('pages::ratings.create')
        ->set('companyId', (string) $company->id)
        ->call('goToStep', 3)
        ->assertHasErrors(['modality'])
        ->assertSet('currentStep', 1);
});

test('rating form initially loads approved companies', function () {
    Company::create(['name' => 'شركة معتمدة', 'type' => 'private', 'status' => 'approved']);
    Company::create(['name' => 'شركة معلقة', 'type' => 'other', 'status' => 'pending']);

    Livewire::test('pages::ratings.create')
        ->assertSet('companyOptions', function ($options) {
            return collect($options)->contains(fn ($o) => data_get($o, 'name') === 'شركة معتمدة')
                && ! collect($options)->contains(fn ($o) => data_get($o, 'name') === 'شركة معلقة');
        });
});

test('search filters companies by name', function () {
    Company::create(['name' => 'شركة أرامكو', 'type' => 'private', 'status' => 'approved']);
    Company::create(['name' => 'شركة سابك', 'type' => 'private', 'status' => 'approved']);

    Livewire::test('pages::ratings.create')
        ->call('searchCompanies', 'أرامكو')
        ->assertSet('companyOptions', function ($options) {
            return collect($options)->contains(fn ($o) => data_get($o, 'name') === 'شركة أرامكو')
                && ! collect($options)->contains(fn ($o) => data_get($o, 'name') === 'شركة سابك');
        });
});

test('search with no matches offers create-new synthetic option', function () {
    Company::create(['name' => 'شركة أرامكو', 'type' => 'private', 'status' => 'approved']);

    Livewire::test('pages::ratings.create')
        ->call('searchCompanies', 'شركة غير موجودة')
        ->assertSet('companyOptions', function ($options) {
            return collect($options)->contains(fn ($o) => data_get($o, 'id') === '__new__'
                && data_get($o, 'name') === 'شركة غير موجودة');
        });
});

test('selecting create-new sets newCompanyName from search', function () {
    Livewire::test('pages::ratings.create')
        ->call('searchCompanies', 'شركة جديدة')
        ->set('companyId', '__new__')
        ->assertSet('newCompanyName', 'شركة جديدة');
});

test('selecting create-new clears stale company search state', function () {
    Livewire::test('pages::ratings.create')
        ->call('searchCompanies', 'شركة جديدة')
        ->set('companyId', '__new__')
        ->assertSet('companySearch', '')
        ->assertSet('companyOptions', [
            ['id' => '__new__', 'name' => 'شركة جديدة'],
        ]);
});

test('create-new company requires selecting company type', function () {
    Livewire::test('pages::ratings.create')
        ->call('searchCompanies', 'شركة جديدة')
        ->set('companyId', '__new__')
        ->set('modality', 'onsite')
        ->call('nextStep')
        ->assertHasErrors(['newCompanyType']);
});

test('valid rating can be submitted for existing company', function () {
    $company = Company::create(['name' => 'شركة تجريبية', 'type' => 'private', 'status' => 'approved']);

    Livewire::test('pages::ratings.create')
        ->set('companyId', (string) $company->id)
        ->set('role_title', 'مهندس برمجيات')
        ->set('department', 'تقنية المعلومات')
        ->set('city', SaudiCity::Riyadh->value)
        ->set('duration_months', 3)
        ->set('modality', 'onsite')
        ->set('stipend_sar', 2500)
        ->set('had_supervisor', true)
        ->set('mixed_env', true)
        ->set('job_offer', false)
        ->set('rating_learning', 5)
        ->set('rating_mentorship', 5)
        ->set('rating_real_work', 4)
        ->set('rating_team_environment', 4)
        ->set('rating_organization', 3)
        ->set('review_text', 'تجربة تدريب ممتازة ومفيدة جداً')
        ->set('pros', 'بيئة مهنية')
        ->set('cons', 'إجراءات بطيئة')
        ->set('reviewer_name', 'أحمد')
        ->set('reviewer_major', 'هندسة البرمجيات')
        ->call('save')
        ->assertRedirect(route('companies.show', $company));

    expect(Rating::where('company_id', $company->id)->count())->toBe(1);

    $rating = Rating::first();
    expect($rating->stipend_sar)->toBe(2500)
        ->and($rating->had_supervisor)->toBeTrue()
        ->and($rating->modality->value)->toBe('onsite')
        ->and($rating->overall_rating)->toBe(4.4)
        ->and($rating->recommendation->value)->toBe('yes');
});

test('submitting with create-new creates pending company and rating', function () {
    Livewire::test('pages::ratings.create')
        ->call('searchCompanies', 'شركة جديدة تماماً')
        ->set('companyId', '__new__')
        ->set('newCompanyType', 'private')
        ->set('role_title', 'مهندس')
        ->set('city', SaudiCity::Riyadh->value)
        ->set('duration_months', 4)
        ->set('modality', 'remote')
        ->set('rating_learning', 4)
        ->set('rating_mentorship', 3)
        ->set('rating_real_work', 3)
        ->set('rating_team_environment', 4)
        ->set('rating_organization', 2)
        ->set('recommendation', 'maybe')
        ->set('review_text', 'تجربة تدريب في شركة جديدة')
        ->call('save')
        ->assertRedirect(route('companies.index'));

    $this->assertDatabaseHas('companies', [
        'name' => 'شركة جديدة تماماً',
        'type' => 'private',
        'status' => 'pending',
    ]);

    $company = Company::where('name', 'شركة جديدة تماماً')->first();
    expect(Rating::where('company_id', $company->id)->count())->toBe(1);
});

test('unpaid internship saves stipend as null', function () {
    $company = Company::create(['name' => 'شركة تجريبية', 'type' => 'private', 'status' => 'approved']);

    Livewire::test('pages::ratings.create')
        ->set('companyId', (string) $company->id)
        ->set('role_title', 'متدرب')
        ->set('city', SaudiCity::Riyadh->value)
        ->set('duration_months', 2)
        ->set('modality', 'onsite')
        ->set('rating_learning', 3)
        ->set('rating_mentorship', 3)
        ->set('rating_real_work', 2)
        ->set('rating_team_environment', 3)
        ->set('rating_organization', 2)
        ->set('review_text', 'تجربة تدريب غير مدفوعة ولكنها مفيدة')
        ->call('save');

    expect(Rating::first()->stipend_sar)->toBeNull()
        ->and(Rating::first()->recommendation->value)->toBe('no');
});

test('rating with missing required fields is rejected', function () {
    $company = Company::create(['name' => 'شركة تجريبية', 'type' => 'private', 'status' => 'approved']);

    Livewire::test('pages::ratings.create')
        ->set('companyId', (string) $company->id)
        ->call('save')
        ->assertHasErrors(['modality', 'rating_mentorship', 'rating_learning', 'rating_real_work', 'rating_team_environment', 'rating_organization', 'recommendation', 'review_text']);
});

test('duration is optional on step 1', function () {
    $company = Company::create(['name' => 'شركة تجريبية', 'type' => 'private', 'status' => 'approved']);

    Livewire::test('pages::ratings.create')
        ->set('companyId', (string) $company->id)
        ->set('role_title', 'مهندس')
        ->set('city', SaudiCity::Riyadh->value)
        ->set('modality', 'onsite')
        ->call('nextStep')
        ->assertHasNoErrors()
        ->assertSet('currentStep', 2);
});

test('duration must be between 1 and 12 months when provided', function () {
    $company = Company::create(['name' => 'شركة تجريبية', 'type' => 'private', 'status' => 'approved']);

    Livewire::test('pages::ratings.create')
        ->set('companyId', (string) $company->id)
        ->set('duration_months', 13)
        ->set('modality', 'onsite')
        ->call('nextStep')
        ->assertHasErrors(['duration_months']);
});

test('rating scores must be between 1 and 5', function () {
    $company = Company::create(['name' => 'شركة تجريبية', 'type' => 'private', 'status' => 'approved']);

    Livewire::test('pages::ratings.create')
        ->set('companyId', (string) $company->id)
        ->set('role_title', 'مبرمج')
        ->set('city', SaudiCity::Riyadh->value)
        ->set('duration_months', 3)
        ->set('modality', 'onsite')
        ->set('rating_learning', 6)
        ->set('rating_mentorship', 3)
        ->set('rating_real_work', 4)
        ->set('rating_team_environment', 4)
        ->set('rating_organization', 3)
        ->set('recommendation', 'yes')
        ->set('review_text', 'تجربة تدريب في شركة تجريبية')
        ->call('save')
        ->assertHasErrors(['rating_learning']);
});

test('modality must be a valid option', function () {
    $company = Company::create(['name' => 'شركة تجريبية', 'type' => 'private', 'status' => 'approved']);

    Livewire::test('pages::ratings.create')
        ->set('companyId', (string) $company->id)
        ->set('role_title', 'مبرمج')
        ->set('duration_months', 3)
        ->set('modality', 'invalid_value')
        ->call('nextStep')
        ->assertHasErrors(['modality']);
});

test('recommendation must be yes maybe or no', function () {
    $company = Company::create(['name' => 'شركة تجريبية', 'type' => 'private', 'status' => 'approved']);

    Livewire::test('pages::ratings.create')
        ->set('companyId', (string) $company->id)
        ->set('role_title', 'مبرمج')
        ->set('city', SaudiCity::Riyadh->value)
        ->set('duration_months', 3)
        ->set('modality', 'onsite')
        ->set('rating_learning', 4)
        ->set('rating_mentorship', 4)
        ->set('rating_real_work', 4)
        ->set('rating_team_environment', 4)
        ->set('rating_organization', 3)
        ->set('recommendation', 'invalid')
        ->set('review_text', 'تجربة مفصّلة في التدريب')
        ->call('save')
        ->assertHasErrors(['recommendation']);
});

test('city must be a valid saudi city', function () {
    $company = Company::create(['name' => 'شركة تجريبية', 'type' => 'private', 'status' => 'approved']);

    Livewire::test('pages::ratings.create')
        ->set('companyId', (string) $company->id)
        ->set('duration_months', 3)
        ->set('modality', 'onsite')
        ->set('city', 'New York')
        ->call('nextStep')
        ->assertHasErrors(['city']);
});

test('city accepts valid saudi city', function () {
    $company = Company::create(['name' => 'شركة تجريبية', 'type' => 'private', 'status' => 'approved']);

    Livewire::test('pages::ratings.create')
        ->set('companyId', (string) $company->id)
        ->set('duration_months', 3)
        ->set('modality', 'onsite')
        ->set('city', SaudiCity::Jeddah->value)
        ->call('nextStep')
        ->assertHasNoErrors(['city']);
});

test('model computes average rating correctly', function () {
    $company = Company::create(['name' => 'شركة', 'type' => 'private', 'status' => 'approved']);

    Rating::create([
        'company_id' => $company->id,
        'role_title' => 'مبرمج',
        'duration_months' => 3,
        'modality' => 'onsite',
        'rating_learning' => 5,
        'rating_mentorship' => 5,
        'rating_real_work' => 4,
        'rating_team_environment' => 4,
        'rating_organization' => 3,
        'review_text' => 'تجربة ممتازة في التدريب',
    ]);

    Rating::create([
        'company_id' => $company->id,
        'role_title' => 'محلل',
        'duration_months' => 6,
        'modality' => 'hybrid',
        'rating_learning' => 4,
        'rating_mentorship' => 3,
        'rating_real_work' => 3,
        'rating_team_environment' => 4,
        'rating_organization' => 3,
        'review_text' => 'تجربة جيدة بشكل عام',
    ]);

    expect($company->average_rating)->toBe(4.0);
});
