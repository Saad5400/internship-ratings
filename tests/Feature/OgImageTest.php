<?php

use App\Models\Company;
use App\Models\Rating;
use App\Support\CompanyOgImage;

/**
 * The social card is a committed artifact rendered by scripts/og-image.mjs. Its
 * dimensions are also hardcoded in the meta partial, so the file and the tags
 * have to agree or crawlers get a card they cannot lay out.
 */
it('ships an og image at the dimensions the meta tags declare', function (): void {
    $path = public_path('og-image.png');

    expect($path)->toBeReadableFile();

    [$width, $height, $type] = getimagesize($path);

    expect($width)->toBe(1200)
        ->and($height)->toBe(630)
        ->and($type)->toBe(IMAGETYPE_PNG);
});

it('points the open graph and twitter tags at that image', function (): void {
    $response = $this->withoutVite()->get(route('companies.index'));

    $response->assertOk()
        ->assertSee('<meta property="og:image" content="'.url('/og-image.png').'">', false)
        ->assertSee('<meta property="og:image:width" content="1200">', false)
        ->assertSee('<meta property="og:image:height" content="630">', false)
        ->assertSee('<meta name="twitter:image" content="'.url('/og-image.png').'">', false);
});

/**
 * Per-company cards are rendered on demand by scripts/og-company.mjs (Takumi)
 * and served from route `companies.og`. The show page points its social tags at
 * that route so a shared link previews with the company's own name and score.
 */
it('points a company page social card at its dynamic og route', function (): void {
    $company = Company::create([
        'name' => 'شركة تجريبية للتقييم',
        'status' => 'approved',
        'description' => 'وصف مخصص لهذه الشركة يظهر في بطاقة المشاركة',
    ]);

    $response = $this->withoutVite()->get(route('companies.show', $company));

    $response->assertOk()
        ->assertSee('<meta property="og:image" content="'.route('companies.og', $company).'">', false)
        ->assertSee('<meta property="og:image:alt" content="شركة تجريبية للتقييم">', false)
        ->assertSee('<meta name="twitter:image" content="'.route('companies.og', $company).'">', false)
        // The company's own description reaches the head too (both are derived
        // in partials/meta.blade for this route).
        ->assertSee('<meta name="description" content="وصف مخصص لهذه الشركة يظهر في بطاقة المشاركة">', false);
});

it('serves a png at the declared dimensions from the company og route', function (): void {
    $company = Company::create(['name' => 'شركة تجريبية للتقييم', 'status' => 'approved']);

    $response = $this->get(route('companies.og', $company));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toBe('image/png');

    [$width, $height, $type] = getimagesizefromstring($response->streamedContent());

    expect($width)->toBe(1200)
        ->and($height)->toBe(630)
        ->and($type)->toBe(IMAGETYPE_PNG);
});

it('hides the company og route for a company that is not approved', function (): void {
    $company = Company::create(['name' => 'شركة معلقة', 'status' => 'pending']);

    $this->get(route('companies.og', $company))->assertNotFound();
});

it('keys the card cache to the facts the card shows', function (): void {
    $company = Company::create(['name' => 'شركة تجريبية', 'status' => 'approved']);
    $ogImage = app(CompanyOgImage::class);

    $before = $ogImage->etag($company->fresh());

    Rating::create([
        'company_id' => $company->id,
        'role_title' => 'مبرمج',
        'duration_months' => 3,
        'modality' => 'onsite',
        'rating_learning' => 5,
        'rating_mentorship' => 5,
        'rating_real_work' => 5,
        'rating_team_environment' => 5,
        'rating_organization' => 5,
        'review_text' => 'تجربة ممتازة',
    ]);

    // A new approved rating moves the average, so the card — and its cache key —
    // must change with it.
    expect($ogImage->etag($company->fresh()))->not->toBe($before);
});

it('builds a payload mirroring the score tiers the site uses', function (): void {
    $company = Company::create([
        'name' => 'شركة تجريبية',
        'type' => 'private',
        'website' => 'https://example.com/careers',
        'status' => 'approved',
    ]);

    Rating::create([
        'company_id' => $company->id,
        'role_title' => 'مبرمج',
        'duration_months' => 3,
        'modality' => 'onsite',
        'rating_learning' => 5,
        'rating_mentorship' => 5,
        'rating_real_work' => 5,
        'rating_team_environment' => 4,
        'rating_organization' => 4,
        'review_text' => 'تجربة ممتازة',
    ]);

    $payload = app(CompanyOgImage::class)->payload($company->fresh());

    expect($payload['name'])->toBe('شركة تجريبية')
        ->and($payload['typeLabel'])->toBe('خاص')
        ->and($payload['scoreTier'])->toBe('good')
        ->and($payload['host'])->toBe('example.com')
        ->and($payload['countLabel'])->not->toBeNull();
});

it('renders a real 1200x630 png through takumi', function (): void {
    $payload = [
        'name' => 'شركة تجريبية للتقييم',
        'typeLabel' => 'خاص',
        'score' => '4.5',
        'scoreTier' => 'good',
        'countLabel' => '24 تقييمًا',
        'host' => 'example.com',
    ];

    try {
        $png = app(CompanyOgImage::class)->render($payload);
    } catch (Throwable $e) {
        test()->markTestSkipped('Takumi render unavailable in this environment: '.$e->getMessage());
    }

    [$width, $height, $type] = getimagesizefromstring($png);

    expect($width)->toBe(1200)
        ->and($height)->toBe(630)
        ->and($type)->toBe(IMAGETYPE_PNG);
});
