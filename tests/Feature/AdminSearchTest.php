<?php

use App\Models\Company;
use App\Models\Rating;
use App\Models\User;

test('the palette search returns grouped matches for an admin', function () {
    $admin = User::factory()->admin()->create();
    $company = Company::create(['name' => 'شركة أرامكو السعودية', 'type' => 'private', 'status' => 'approved']);
    Rating::create([
        'company_id' => $company->id,
        'role_title' => 'مهندس برمجيات',
        'modality' => 'onsite',
        'rating_learning' => 5,
        'rating_mentorship' => 4,
        'rating_real_work' => 4,
        'rating_team_environment' => 3,
        'rating_organization' => 5,
        'status' => 'approved',
    ]);

    $response = $this->actingAs($admin)
        ->getJson('/admin/search?q=ارامكو')
        ->assertOk()
        ->json();

    $titles = collect($response['groups'])->pluck('title');
    expect($titles)->toContain('الجهات')
        ->and($titles)->toContain('التقييمات');

    $companyGroup = collect($response['groups'])->firstWhere('title', 'الجهات');
    expect($companyGroup['items'][0]['label'])->toBe('شركة أرامكو السعودية');
});

test('the palette search matches users by email', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create(['email' => 'target@example.com', 'name' => 'مستخدم مستهدف']);

    $response = $this->actingAs($admin)
        ->getJson('/admin/search?q=target@example')
        ->assertOk()
        ->json();

    $userGroup = collect($response['groups'])->firstWhere('title', 'المستخدمون');
    expect($userGroup['items'][0]['label'])->toBe('مستخدم مستهدف');
});

test('queries shorter than two characters return no groups', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->getJson('/admin/search?q=ا')
        ->assertOk()
        ->assertExactJson(['groups' => []]);
});

test('the palette search is admin-only', function () {
    $this->getJson('/admin/search?q=test')->assertUnauthorized();

    $this->actingAs(User::factory()->create())
        ->getJson('/admin/search?q=test')
        ->assertForbidden();
});
