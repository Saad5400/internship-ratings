<?php

use App\Enums\SaudiCity;
use App\Filament\Resources\Ratings\Pages\ListRatings;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\Company;
use App\Models\Rating;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

test('creating a user with admin access persists is_admin and a hashed password', function () {
    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'مستخدم جديد',
            'email' => 'new-admin@example.com',
            'password' => 'secret-password',
            'is_admin' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $user = User::query()->where('email', 'new-admin@example.com')->firstOrFail();

    expect($user->is_admin)->toBeTrue()
        ->and($user->password)->not->toBe('secret-password')
        ->and(Hash::check('secret-password', $user->password))->toBeTrue();
});

test('creating a user with a duplicate email is rejected', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'مكرر',
            'email' => 'taken@example.com',
            'password' => 'secret-password',
            'is_admin' => true,
        ])
        ->call('create')
        ->assertHasFormErrors(['email']);
});

test('editing a user with a blank password keeps the current password', function () {
    $user = User::factory()->create([
        'email' => 'keep-pass@example.com',
        'password' => Hash::make('original-password'),
    ]);

    $originalHash = $user->password;

    Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
        ->fillForm([
            'name' => 'اسم محدث',
            'email' => 'keep-pass@example.com',
            'password' => '',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $user->refresh();

    expect($user->name)->toBe('اسم محدث')
        ->and($user->password)->toBe($originalHash)
        ->and(Hash::check('original-password', $user->password))->toBeTrue();
});

test('the last admin cannot be demoted', function () {
    // The admin from beforeEach is the only admin.
    Livewire::test(EditUser::class, ['record' => $this->admin->getRouteKey()])
        ->fillForm([
            'is_admin' => false,
        ])
        ->call('save')
        ->assertHasFormErrors(['is_admin']);

    expect($this->admin->fresh()->is_admin)->toBeTrue();
});

test('an admin can be demoted when another admin remains', function () {
    User::factory()->admin()->create();

    $target = User::factory()->admin()->create();

    Livewire::test(EditUser::class, ['record' => $target->getRouteKey()])
        ->fillForm([
            'is_admin' => false,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($target->fresh()->is_admin)->toBeFalse();
});

test('the delete action is hidden for the last admin', function () {
    Livewire::test(EditUser::class, ['record' => $this->admin->getRouteKey()])
        ->assertActionHidden('delete');
});

test('approving a pending rating from the table sets its status to approved', function () {
    $company = Company::create(['name' => 'شركة تجريبية', 'type' => 'private', 'status' => 'approved']);

    $rating = Rating::create([
        'company_id' => $company->id,
        'role_title' => 'مهندس',
        'city' => SaudiCity::Riyadh->value,
        'modality' => 'onsite',
        'rating_learning' => 5,
        'rating_mentorship' => 5,
        'rating_real_work' => 5,
        'rating_team_environment' => 5,
        'rating_organization' => 5,
        'status' => 'pending',
    ]);

    Livewire::test(ListRatings::class)
        ->callAction(TestAction::make('approve')->table($rating));

    expect($rating->fresh()->status)->toBe('approved');
});
