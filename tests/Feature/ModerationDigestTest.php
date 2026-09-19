<?php

use App\Mail\PendingModerationDigest;
use App\Models\Company;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Mail;

/**
 * @param  array<string, mixed>  $overrides
 */
function digestPendingRating(Company $company, array $overrides = []): Rating
{
    return Rating::create(array_merge([
        'company_id' => $company->id,
        'role_title' => 'مهندس برمجيات',
        'modality' => 'onsite',
        'rating_learning' => 5,
        'rating_mentorship' => 4,
        'rating_real_work' => 4,
        'rating_team_environment' => 3,
        'rating_organization' => 5,
        'status' => 'pending',
    ], $overrides));
}

test('the digest is sent to every admin and to no one else when ratings are pending', function () {
    Mail::fake();
    $admins = User::factory()->admin()->count(2)->create();
    $member = User::factory()->create();
    $company = Company::create(['name' => 'جهة', 'type' => 'private', 'status' => 'approved']);
    digestPendingRating($company);

    $this->artisan('moderation:digest')->assertSuccessful();

    foreach ($admins as $admin) {
        Mail::assertQueued(PendingModerationDigest::class, fn ($mail) => $mail->hasTo($admin->email));
    }
    Mail::assertNotQueued(PendingModerationDigest::class, fn ($mail) => $mail->hasTo($member->email));
    Mail::assertQueuedCount(2);
});

test('the digest is sent when only a company is pending', function () {
    Mail::fake();
    $admin = User::factory()->admin()->create();
    Company::create(['name' => 'جهة قيد المراجعة', 'type' => 'private', 'status' => 'pending']);

    $this->artisan('moderation:digest')->assertSuccessful();

    Mail::assertQueued(PendingModerationDigest::class, fn ($mail) => $mail->hasTo($admin->email)
        && $mail->pendingRatings === 0
        && $mail->pendingCompanies === 1);
});

test('no digest is sent when nothing is pending', function () {
    Mail::fake();
    User::factory()->admin()->create();
    Company::create(['name' => 'جهة معتمدة', 'type' => 'private', 'status' => 'approved']);

    $this->artisan('moderation:digest')->assertSuccessful();

    Mail::assertNothingQueued();
});

test('a second run within 48 hours sends nothing', function () {
    Mail::fake();
    User::factory()->admin()->create();
    Company::create(['name' => 'جهة', 'type' => 'private', 'status' => 'pending']);

    $this->artisan('moderation:digest')->assertSuccessful();
    $this->artisan('moderation:digest')->assertSuccessful();

    Mail::assertQueuedCount(1);
});

test('the digest is sent again once two days have passed', function () {
    Mail::fake();
    User::factory()->admin()->create();
    Company::create(['name' => 'جهة', 'type' => 'private', 'status' => 'pending']);

    $this->artisan('moderation:digest')->assertSuccessful();
    $this->travel(2)->days();
    $this->artisan('moderation:digest')->assertSuccessful();

    Mail::assertQueuedCount(2);
});

test('--force bypasses the 48-hour window', function () {
    Mail::fake();
    User::factory()->admin()->create();
    Company::create(['name' => 'جهة', 'type' => 'private', 'status' => 'pending']);

    $this->artisan('moderation:digest')->assertSuccessful();
    $this->artisan('moderation:digest', ['--force' => true])->assertSuccessful();

    Mail::assertQueuedCount(2);
});

test('a run with nothing pending does not consume the 48-hour window', function () {
    Mail::fake();
    User::factory()->admin()->create();

    $this->artisan('moderation:digest')->assertSuccessful();
    Company::create(['name' => 'جهة', 'type' => 'private', 'status' => 'pending']);
    $this->artisan('moderation:digest')->assertSuccessful();

    Mail::assertQueuedCount(1);
});

test('the email renders Arabic RTL content with counts and a dashboard link', function () {
    $mail = new PendingModerationDigest(pendingRatings: 3, pendingCompanies: 1);

    $mail->assertHasSubject('بانتظارك 3 تقييمات وجهة واحدة للمراجعة');
    $mail->assertSeeInHtml('dir="rtl"', escape: false);
    $mail->assertSeeInHtml('3 تقييمات');
    $mail->assertSeeInHtml('جهة واحدة');
    $mail->assertSeeInHtml(route('admin.dashboard'), escape: false);
});

test('the subject omits the noun with a zero count', function () {
    (new PendingModerationDigest(pendingRatings: 0, pendingCompanies: 2))
        ->assertHasSubject('بانتظارك جهتان للمراجعة');
});

test('the digest is scheduled daily on one server', function () {
    // Running any artisan command boots the console kernel, which registers
    // the schedule from routes/console.php on the Schedule singleton.
    $this->artisan('schedule:list')->assertSuccessful();

    $event = collect(app(Schedule::class)->events())
        ->first(fn ($event) => str_contains((string) $event->command, 'moderation:digest'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('0 6 * * *')
        ->and($event->onOneServer)->toBeTrue();
});

test('the command succeeds quietly when no admins exist', function () {
    Mail::fake();
    Company::create(['name' => 'جهة', 'type' => 'private', 'status' => 'pending']);

    $this->artisan('moderation:digest')->assertSuccessful();

    Mail::assertNothingQueued();
});
