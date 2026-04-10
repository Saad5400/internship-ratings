<?php

use App\Models\Company;
use App\Models\Rating;
use Livewire\Livewire;

test('rating form is accessible', function () {
    $response = $this->get(route('ratings.create'));

    $response->assertOk();
    $response->assertSee('أضف تقييم');
});

test('wizard starts on step 1', function () {
    Livewire::test('pages::ratings.create')
        ->assertSet('currentStep', 1);
});

test('cannot advance past step 1 without required fields', function () {
    Livewire::test('pages::ratings.create')
        ->call('nextStep')
        ->assertHasErrors(['companyId', 'role_title', 'duration_months', 'modality'])
        ->assertSet('currentStep', 1);
});

test('can advance past step 1 with all required fields filled', function () {
    $company = Company::create(['name' => 'شركة تجريبية', 'status' => 'approved']);

    Livewire::test('pages::ratings.create')
        ->set('companyId', (string) $company->id)
        ->set('role_title', 'مهندس')
        ->set('duration_months', 3)
        ->set('modality', 'onsite')
        ->call('nextStep')
        ->assertHasNoErrors()
        ->assertSet('currentStep', 2);
});

test('cannot advance past step 2 without required scores', function () {
    $company = Company::create(['name' => 'شركة تجريبية', 'status' => 'approved']);

    Livewire::test('pages::ratings.create')
        ->set('companyId', (string) $company->id)
        ->set('role_title', 'مهندس')
        ->set('duration_months', 3)
        ->set('modality', 'onsite')
        ->call('nextStep')
        ->call('nextStep')
        ->assertHasErrors(['rating_mentorship', 'rating_learning', 'rating_culture', 'rating_compensation', 'overall_rating'])
        ->assertSet('currentStep', 2);
});

test('can navigate backwards freely without validation', function () {
    $company = Company::create(['name' => 'شركة تجريبية', 'status' => 'approved']);

    Livewire::test('pages::ratings.create')
        ->set('companyId', (string) $company->id)
        ->set('role_title', 'مهندس')
        ->set('duration_months', 3)
        ->set('modality', 'onsite')
        ->call('nextStep')
        ->set('rating_mentorship', 4)
        ->set('rating_learning', 4)
        ->set('rating_culture', 4)
        ->set('rating_compensation', 3)
        ->set('overall_rating', 4)
        ->call('nextStep')
        ->assertSet('currentStep', 3)
        ->call('prevStep')
        ->assertSet('currentStep', 2)
        ->assertHasNoErrors();
});

test('goToStep blocks forward jumps when intermediate steps are invalid', function () {
    $company = Company::create(['name' => 'شركة تجريبية', 'status' => 'approved']);

    Livewire::test('pages::ratings.create')
        ->set('companyId', (string) $company->id)
        ->call('goToStep', 3)
        ->assertHasErrors(['role_title'])
        ->assertSet('currentStep', 1);
});

test('rating form initially loads approved companies', function () {
    Company::create(['name' => 'شركة معتمدة', 'status' => 'approved']);
    Company::create(['name' => 'شركة معلقة', 'status' => 'pending']);

    Livewire::test('pages::ratings.create')
        ->assertSet('companyOptions', function ($options) {
            return collect($options)->contains(fn ($o) => data_get($o, 'name') === 'شركة معتمدة')
                && ! collect($options)->contains(fn ($o) => data_get($o, 'name') === 'شركة معلقة');
        });
});

test('search filters companies by name', function () {
    Company::create(['name' => 'شركة أرامكو', 'status' => 'approved']);
    Company::create(['name' => 'شركة سابك', 'status' => 'approved']);

    Livewire::test('pages::ratings.create')
        ->call('searchCompanies', 'أرامكو')
        ->assertSet('companyOptions', function ($options) {
            return collect($options)->contains(fn ($o) => data_get($o, 'name') === 'شركة أرامكو')
                && ! collect($options)->contains(fn ($o) => data_get($o, 'name') === 'شركة سابك');
        });
});

test('search with no matches offers create-new synthetic option', function () {
    Company::create(['name' => 'شركة أرامكو', 'status' => 'approved']);

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

test('valid rating can be submitted for existing company', function () {
    $company = Company::create(['name' => 'شركة تجريبية', 'status' => 'approved']);

    Livewire::test('pages::ratings.create')
        ->set('companyId', (string) $company->id)
        ->set('role_title', 'مهندس برمجيات')
        ->set('department', 'تقنية المعلومات')
        ->set('city', 'الرياض')
        ->set('duration_months', 3)
        ->set('sector', 'private')
        ->set('modality', 'onsite')
        ->set('stipend_sar', 2500)
        ->set('had_supervisor', true)
        ->set('mixed_env', true)
        ->set('job_offer', false)
        ->set('rating_mentorship', 5)
        ->set('rating_learning', 5)
        ->set('rating_culture', 4)
        ->set('rating_compensation', 3)
        ->set('overall_rating', 4)
        ->set('recommendation', 'yes')
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
        ->and($rating->modality)->toBe('onsite')
        ->and($rating->recommendation)->toBe('yes');
});

test('submitting with create-new creates pending company and rating', function () {
    Livewire::test('pages::ratings.create')
        ->call('searchCompanies', 'شركة جديدة تماماً')
        ->set('companyId', '__new__')
        ->set('role_title', 'مهندس')
        ->set('duration_months', 4)
        ->set('modality', 'remote')
        ->set('rating_mentorship', 3)
        ->set('rating_learning', 4)
        ->set('rating_culture', 4)
        ->set('rating_compensation', 2)
        ->set('overall_rating', 3)
        ->set('recommendation', 'maybe')
        ->set('review_text', 'تجربة تدريب في شركة جديدة')
        ->call('save')
        ->assertRedirect(route('companies.index'));

    $this->assertDatabaseHas('companies', [
        'name' => 'شركة جديدة تماماً',
        'status' => 'pending',
    ]);

    $company = Company::where('name', 'شركة جديدة تماماً')->first();
    expect(Rating::where('company_id', $company->id)->count())->toBe(1);
});

test('unpaid internship saves stipend as null', function () {
    $company = Company::create(['name' => 'شركة تجريبية', 'status' => 'approved']);

    Livewire::test('pages::ratings.create')
        ->set('companyId', (string) $company->id)
        ->set('role_title', 'متدرب')
        ->set('duration_months', 2)
        ->set('modality', 'onsite')
        ->set('rating_mentorship', 3)
        ->set('rating_learning', 3)
        ->set('rating_culture', 3)
        ->set('rating_compensation', 2)
        ->set('overall_rating', 3)
        ->set('recommendation', 'maybe')
        ->set('review_text', 'تجربة تدريب غير مدفوعة ولكنها مفيدة')
        ->call('save');

    expect(Rating::first()->stipend_sar)->toBeNull();
});

test('rating with missing required fields is rejected', function () {
    $company = Company::create(['name' => 'شركة تجريبية', 'status' => 'approved']);

    Livewire::test('pages::ratings.create')
        ->set('companyId', (string) $company->id)
        ->call('save')
        ->assertHasErrors(['role_title', 'duration_months', 'modality', 'rating_mentorship', 'rating_learning', 'rating_culture', 'rating_compensation', 'overall_rating', 'recommendation', 'review_text']);
});

test('rating scores must be between 1 and 5', function () {
    $company = Company::create(['name' => 'شركة تجريبية', 'status' => 'approved']);

    Livewire::test('pages::ratings.create')
        ->set('companyId', (string) $company->id)
        ->set('role_title', 'مبرمج')
        ->set('duration_months', 3)
        ->set('modality', 'onsite')
        ->set('rating_mentorship', 3)
        ->set('rating_learning', 6)
        ->set('rating_culture', 4)
        ->set('rating_compensation', 3)
        ->set('overall_rating', 0)
        ->set('recommendation', 'yes')
        ->set('review_text', 'تجربة تدريب في شركة تجريبية')
        ->call('save')
        ->assertHasErrors(['rating_learning', 'overall_rating']);
});

test('modality must be a valid option', function () {
    $company = Company::create(['name' => 'شركة تجريبية', 'status' => 'approved']);

    Livewire::test('pages::ratings.create')
        ->set('companyId', (string) $company->id)
        ->set('role_title', 'مبرمج')
        ->set('duration_months', 3)
        ->set('modality', 'invalid_value')
        ->call('nextStep')
        ->assertHasErrors(['modality']);
});

test('recommendation must be yes maybe or no', function () {
    $company = Company::create(['name' => 'شركة تجريبية', 'status' => 'approved']);

    Livewire::test('pages::ratings.create')
        ->set('companyId', (string) $company->id)
        ->set('role_title', 'مبرمج')
        ->set('duration_months', 3)
        ->set('modality', 'onsite')
        ->set('rating_mentorship', 4)
        ->set('rating_learning', 4)
        ->set('rating_culture', 4)
        ->set('rating_compensation', 3)
        ->set('overall_rating', 4)
        ->set('recommendation', 'invalid')
        ->set('review_text', 'تجربة مفصّلة في التدريب')
        ->call('save')
        ->assertHasErrors(['recommendation']);
});

test('model computes average rating correctly', function () {
    $company = Company::create(['name' => 'شركة', 'status' => 'approved']);

    Rating::create([
        'company_id' => $company->id,
        'role_title' => 'مبرمج',
        'duration_months' => 3,
        'modality' => 'onsite',
        'rating_mentorship' => 4,
        'rating_learning' => 4,
        'rating_culture' => 5,
        'rating_compensation' => 4,
        'overall_rating' => 5,
        'recommendation' => 'yes',
        'review_text' => 'تجربة ممتازة في التدريب',
    ]);

    Rating::create([
        'company_id' => $company->id,
        'role_title' => 'محلل',
        'duration_months' => 6,
        'modality' => 'hybrid',
        'rating_mentorship' => 3,
        'rating_learning' => 3,
        'rating_culture' => 3,
        'rating_compensation' => 2,
        'overall_rating' => 3,
        'recommendation' => 'maybe',
        'review_text' => 'تجربة جيدة بشكل عام',
    ]);

    expect($company->average_rating)->toBe(4.0);
});
