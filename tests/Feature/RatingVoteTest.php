<?php

use App\Models\Company;
use App\Models\Rating;
use App\Models\RatingVote;
use Illuminate\Database\QueryException;
use Livewire\Livewire;

function createTestRating(Company $company): Rating
{
    return Rating::create([
        'company_id' => $company->id,
        'role_title' => 'مبرمج',
        'duration_months' => 3,
        'modality' => 'onsite',
        'rating_learning' => 5,
        'rating_mentorship' => 4,
        'rating_real_work' => 4,
        'rating_team_environment' => 4,
        'rating_organization' => 3,
        'review_text' => 'تجربة جيدة',
    ]);
}

test('creating a vote increments the rating vote count', function () {
    $company = Company::create(['name' => 'شركة', 'type' => 'private', 'status' => 'approved']);
    $rating = createTestRating($company);

    RatingVote::create([
        'rating_id' => $rating->id,
        'voter_hash' => Rating::voterHash('session-one'),
    ]);

    expect($rating->votes()->count())->toBe(1);
});

test('duplicate vote from the same voter hash is rejected', function () {
    $company = Company::create(['name' => 'شركة', 'type' => 'private', 'status' => 'approved']);
    $rating = createTestRating($company);
    $voterHash = Rating::voterHash('session-two');

    RatingVote::create([
        'rating_id' => $rating->id,
        'voter_hash' => $voterHash,
    ]);

    expect(fn () => RatingVote::create([
        'rating_id' => $rating->id,
        'voter_hash' => $voterHash,
    ]))->toThrow(QueryException::class);

    expect($rating->votes()->count())->toBe(1);
});

test('firstOrCreate on the unique pair keeps the vote count stable', function () {
    $company = Company::create(['name' => 'شركة', 'type' => 'private', 'status' => 'approved']);
    $rating = createTestRating($company);
    $voterHash = Rating::voterHash('session-three');

    RatingVote::firstOrCreate([
        'rating_id' => $rating->id,
        'voter_hash' => $voterHash,
    ]);

    RatingVote::firstOrCreate([
        'rating_id' => $rating->id,
        'voter_hash' => $voterHash,
    ]);

    expect($rating->votes()->count())->toBe(1);
});

test('deleting a rating cascades to its votes', function () {
    $company = Company::create(['name' => 'شركة', 'type' => 'private', 'status' => 'approved']);
    $rating = createTestRating($company);

    RatingVote::create([
        'rating_id' => $rating->id,
        'voter_hash' => Rating::voterHash('session-four'),
    ]);

    $rating->delete();

    expect(RatingVote::count())->toBe(0);
});

test('voter hash is a deterministic sha256 digest', function () {
    expect(Rating::voterHash('same-session'))
        ->toBe(hash('sha256', 'same-session'))
        ->toBe(Rating::voterHash('same-session'));
});

test('voting helpful on the company show page records a vote and updates the count', function () {
    $company = Company::create(['name' => 'شركة', 'type' => 'private', 'status' => 'approved']);
    $rating = createTestRating($company);
    $rating->update(['status' => 'approved']);

    Livewire::test('pages::companies.show', ['company' => $company])
        ->call('voteHelpful', $rating->id)
        ->assertSet('votedRatingIds', [$rating->id])
        ->assertSee('مفيد');

    expect(RatingVote::where('rating_id', $rating->id)->count())->toBe(1);
});

test('voting helpful twice from the same session does not duplicate the vote', function () {
    $company = Company::create(['name' => 'شركة', 'type' => 'private', 'status' => 'approved']);
    $rating = createTestRating($company);
    $rating->update(['status' => 'approved']);

    $component = Livewire::test('pages::companies.show', ['company' => $company])
        ->call('voteHelpful', $rating->id)
        ->call('voteHelpful', $rating->id);

    $component->assertSet('votedRatingIds', [$rating->id]);

    expect(RatingVote::where('rating_id', $rating->id)->count())->toBe(1);
});

test('voting helpful on a non-approved rating is ignored', function () {
    $company = Company::create(['name' => 'شركة', 'type' => 'private', 'status' => 'approved']);
    $rating = createTestRating($company);
    $rating->update(['status' => 'pending']);

    Livewire::test('pages::companies.show', ['company' => $company])
        ->call('voteHelpful', $rating->id)
        ->assertSet('votedRatingIds', []);

    expect(RatingVote::where('rating_id', $rating->id)->count())->toBe(0);
});

test('voting helpful on another company rating is ignored', function () {
    $company = Company::create(['name' => 'شركة أولى', 'type' => 'private', 'status' => 'approved']);
    $otherCompany = Company::create(['name' => 'شركة أخرى', 'type' => 'private', 'status' => 'approved']);
    $otherRating = createTestRating($otherCompany);
    $otherRating->update(['status' => 'approved']);

    Livewire::test('pages::companies.show', ['company' => $company])
        ->call('voteHelpful', $otherRating->id)
        ->assertSet('votedRatingIds', []);

    expect(RatingVote::where('rating_id', $otherRating->id)->count())->toBe(0);
});
