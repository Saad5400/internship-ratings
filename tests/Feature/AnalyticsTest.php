<?php

/**
 * The analytics wiring: the two Umami scripts, the server-flashed event queue,
 * and the handful of funnel events the app dispatches.
 *
 * These are here to protect three things that fail silently if they break —
 * a recorder that films developers, a tracker accidentally replaced by the
 * recorder, and a submit path that stops counting its own failures.
 */

use App\Enums\SaudiCity;
use App\Models\Company;
use App\Models\Rating;
use App\Models\User;
use App\Support\Analytics;
use Livewire\Livewire;

/*
 * `Analytics::track()` no-ops without a started session (so console commands and
 * jobs never have to guard). A Livewire unit test has a bound-but-unstarted
 * session, unlike the real request, hence the explicit `startSession()` in the
 * tests that exercise the flashed path.
 */

function analyticsProductionUrl(string $path = '/companies'): string
{
    return 'https://'.config('app.production_host').$path;
}

test('the public shell loads the tracker scoped to the production domain', function () {
    $this->withoutVite()
        ->get(route('companies.index'))
        ->assertOk()
        ->assertSee('analytics.sb.sa/script.js', false)
        ->assertSee('data-domains="'.config('app.production_host').'"', false);
});

test('the recorder is an addition to the tracker, never a replacement', function () {
    $response = $this->withoutVite()->get(analyticsProductionUrl())->assertOk();

    $response->assertSee('analytics.sb.sa/script.js', false)
        ->assertSee('analytics.sb.sa/recorder.js', false);
});

test('session replay stays off every host but production', function () {
    $this->withoutVite()
        ->get(route('companies.index'))
        ->assertOk()
        ->assertDontSee('recorder.js', false);
});

test('the admin panel is counted but never recorded', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->withoutVite()
        ->get(analyticsProductionUrl('/admin'))
        ->assertOk()
        ->assertSee('analytics.sb.sa/script.js', false)
        ->assertDontSee('recorder.js', false);
});

test('flashed events reach the page as data, not as an inline call', function () {
    $this->startSession();
    Analytics::track('rating_submitted', ['company' => 'existing', 'review' => 'yes']);

    $this->withoutVite()
        ->get(route('companies.index'))
        ->assertOk()
        ->assertSee('<script type="application/json" id="analytics-events">', false)
        ->assertSee('rating_submitted', false);
});

test('a page with nothing to report prints no payload at all', function () {
    $this->withoutVite()
        ->get(route('companies.index'))
        ->assertOk()
        ->assertDontSee('id="analytics-events"', false);
});

test('the flashed queue is bounded so a redirect chain cannot grow it', function () {
    $this->startSession();

    foreach (range(1, 25) as $i) {
        Analytics::track('rating_submitted');
    }

    expect(session('analytics'))->toHaveCount(10);
});

test('counts collapse into bands, never raw numbers', function () {
    expect(Analytics::band(0))->toBe('0')
        ->and(Analytics::band(2))->toBe('1-3')
        ->and(Analytics::band(9))->toBe('4-10')
        ->and(Analytics::band(400))->toBe('10+');
});

test('a settled search is counted with the shape of its results, never the query', function () {
    Company::create(['name' => 'شركة تجريبية', 'type' => 'private', 'status' => 'approved']);

    Livewire::test('pages::companies.index')
        ->set('search', 'لا يوجد شيء بهذا الاسم إطلاقاً')
        ->assertDispatched('analytics', function (string $event, array $params): bool {
            return $params['name'] === 'company_search'
                && $params['data'] === ['source' => 'typed', 'results' => '0']
                // The typed query must never travel with the event.
                && ! str_contains(json_encode($params, JSON_UNESCAPED_UNICODE), 'إطلاقاً');
        });
});

test('clearing the search box is not counted as a search', function () {
    Livewire::test('pages::companies.index')
        ->set('search', '')
        ->assertNotDispatched('analytics');
});

test('filters report which dimension was used, not which value', function () {
    Livewire::test('pages::companies.index')
        ->call('toggleFilter', 'city', SaudiCity::Riyadh->value)
        ->assertDispatched('analytics', function (string $event, array $params): bool {
            return $params['name'] === 'company_filter'
                && $params['data'] === ['facet' => 'city', 'action' => 'add']
                && ! str_contains(json_encode($params, JSON_UNESCAPED_UNICODE), SaudiCity::Riyadh->value);
        });
});

test('a wizard step that refuses to advance says so', function () {
    Livewire::test('pages::ratings.create')
        ->call('nextStep')
        ->assertHasErrors()
        ->assertDispatched('analytics', fn (string $event, array $params): bool => $params['name'] === 'rating_step_blocked'
            && $params['data']['step'] === 1
            && $params['data']['field'] === 'companyId');
});

test('a step that advances is counted with the step reached', function () {
    $company = Company::create(['name' => 'شركة تجريبية', 'type' => 'private', 'status' => 'approved']);

    Livewire::test('pages::ratings.create')
        ->set('companyId', (string) $company->id)
        ->set('city', SaudiCity::Riyadh->value)
        ->set('modality', 'onsite')
        ->call('nextStep')
        ->assertHasNoErrors()
        ->assertDispatched('analytics', fn (string $event, array $params): bool => $params['name'] === 'rating_step_advanced'
            && $params['data'] === ['step' => 2]);
});

test('a blocked submit is counted, so a broken form cannot look like a quiet week', function () {
    Livewire::test('pages::ratings.create')
        ->call('save')
        ->assertHasErrors()
        ->assertDispatched('analytics', fn (string $event, array $params): bool => $params['name'] === 'rating_submit_blocked'
            && $params['data']['reason'] === 'validation');
});

test('a successful submit is flashed through the redirect with no review content', function () {
    $this->startSession();

    $company = Company::create(['name' => 'شركة تجريبية', 'type' => 'private', 'status' => 'approved']);

    Livewire::test('pages::ratings.create')
        ->set('companyId', (string) $company->id)
        ->set('city', SaudiCity::Riyadh->value)
        ->set('modality', 'onsite')
        ->set('rating_learning', 5)
        ->set('rating_mentorship', 5)
        ->set('rating_real_work', 4)
        ->set('rating_team_environment', 4)
        ->set('rating_organization', 3)
        ->set('review_text', 'تجربة تدريب ممتازة')
        ->set('reviewer_name', 'أحمد')
        ->call('save')
        ->assertRedirect(route('companies.show', $company));

    $events = collect(session('analytics'));
    $event = $events->firstWhere('name', 'rating_submitted');

    expect($event)->not->toBeNull()
        ->and($event['data'])->toBe(['company' => 'existing', 'review' => 'yes'])
        ->and($event['id'])->toBeString();

    // Nothing about the review itself — not the reviewer, not the scores, not
    // the company — may ride along to a system with no access control.
    $encoded = json_encode($events, JSON_UNESCAPED_UNICODE);
    expect($encoded)->not->toContain('أحمد')
        ->not->toContain('تجربة تدريب ممتازة')
        ->not->toContain('شركة تجريبية');
});

test('submitting against a new employer reports only that it was new', function () {
    $this->startSession();

    Livewire::test('pages::ratings.create')
        ->call('searchCompanies', 'شركة جديدة تماماً')
        ->set('companyId', '__new__')
        ->set('newCompanyType', 'private')
        ->set('city', SaudiCity::Riyadh->value)
        ->set('modality', 'remote')
        ->set('rating_learning', 4)
        ->set('rating_mentorship', 3)
        ->set('rating_real_work', 3)
        ->set('rating_team_environment', 4)
        ->set('rating_organization', 2)
        ->call('save');

    $event = collect(session('analytics'))->firstWhere('name', 'rating_submitted');

    expect($event['data'])->toBe(['company' => 'new', 'review' => 'no']);
});

test('helpful votes and contact reveals are counted without pointing at a review', function () {
    $company = Company::create(['name' => 'شركة تجريبية', 'type' => 'private', 'status' => 'approved']);
    $rating = Rating::create([
        'company_id' => $company->id,
        'city' => SaudiCity::Riyadh->value,
        'modality' => 'onsite',
        'rating_learning' => 4,
        'rating_mentorship' => 4,
        'rating_real_work' => 4,
        'rating_team_environment' => 4,
        'rating_organization' => 4,
        'willing_to_help' => true,
        'contact_method' => 'x@example.com',
        'status' => 'approved',
    ]);

    Livewire::test('pages::companies.show', ['company' => $company])
        ->call('voteHelpful', $rating->id)
        ->assertDispatched('analytics', fn (string $event, array $params): bool => $params['name'] === 'rating_vote'
            && $params['data'] === ['action' => 'add'])
        ->call('revealContact', $rating->id)
        ->assertDispatched('analytics', function (string $event, array $params) use ($rating): bool {
            return $params['name'] === 'rating_contact_revealed'
                && $params['data'] === []
                && ! str_contains(json_encode($params), (string) $rating->id)
                && ! str_contains(json_encode($params), 'x@example.com');
        });
});
