<?php

use App\Models\Company;
use App\Support\CompanyBranding;
use Illuminate\Support\Facades\Http;

/**
 * The suite never reaches the network: only the `--verify` tests exercise the
 * HTTP path and they fake it.
 */
beforeEach(function () {
    Http::preventStrayRequests();
});

test('a company missing both a website and a logo gets both filled', function () {
    $company = Company::create(['name' => 'Lean Node', 'type' => 'private', 'status' => 'approved']);

    expect($company->website)->toBeNull()
        ->and($company->favicon_url)->toBeNull();

    $this->artisan('companies:backfill-branding --apply')
        ->assertSuccessful();

    $company->refresh();

    expect($company->website)->toBe('https://leannode.com')
        ->and($company->favicon_url)->toBe('https://www.google.com/s2/favicons?domain=leannode.com&sz=128');
});

test('a company that already has a website is left untouched', function () {
    $company = Company::create([
        'name' => 'SharedTech',
        'type' => 'private',
        'status' => 'approved',
        'website' => 'https://original.example',
    ]);

    $this->artisan('companies:backfill-branding --apply')
        ->assertSuccessful();

    expect($company->refresh()->website)->toBe('https://original.example');
});

test('the dry run is the default and writes nothing', function () {
    $company = Company::create(['name' => 'Fix Tag', 'type' => 'private', 'status' => 'approved']);

    $this->artisan('companies:backfill-branding')
        ->expectsOutputToContain('https://fixtag.com')
        ->assertSuccessful();

    expect($company->refresh()->website)->toBeNull();
});

test('an explicit --dry-run also writes nothing and refuses to be combined with --apply', function () {
    $company = Company::create(['name' => 'Fix Tag', 'type' => 'private', 'status' => 'approved']);

    $this->artisan('companies:backfill-branding --dry-run')->assertSuccessful();
    expect($company->refresh()->website)->toBeNull();

    $this->artisan('companies:backfill-branding --dry-run --apply')->assertFailed();
    expect($company->refresh()->website)->toBeNull();
});

test('an Arabic-only name is reported as skipped rather than guessed at', function () {
    $company = Company::create(['name' => 'شركة قبس التقنية', 'type' => 'private', 'status' => 'approved']);

    $this->artisan('companies:backfill-branding --apply')
        ->expectsOutputToContain('Arabic-only name')
        ->assertSuccessful();

    expect($company->refresh()->website)->toBeNull();
});

test('--limit caps how many companies are considered', function () {
    $first = Company::create(['name' => 'Fix Tag', 'type' => 'private', 'status' => 'approved']);
    $second = Company::create(['name' => 'Lean Node', 'type' => 'private', 'status' => 'approved']);

    $this->artisan('companies:backfill-branding --apply --limit=1')->assertSuccessful();

    expect($first->refresh()->website)->toBe('https://fixtag.com')
        ->and($second->refresh()->website)->toBeNull();
});

test('--verify drops candidates whose host does not resolve', function () {
    Http::fake(['*' => Http::response('', 404)]);

    $company = Company::create(['name' => 'Lean Node', 'type' => 'private', 'status' => 'approved']);

    $this->artisan('companies:backfill-branding --apply --verify')
        ->expectsOutputToContain('did not resolve')
        ->assertSuccessful();

    Http::assertSent(fn ($request): bool => $request->url() === 'https://leannode.com');

    expect($company->refresh()->website)->toBeNull();
});

test('--verify keeps candidates whose host answers', function () {
    Http::fake(['*' => Http::response('', 200)]);

    $company = Company::create(['name' => 'Lean Node', 'type' => 'private', 'status' => 'approved']);

    $this->artisan('companies:backfill-branding --apply --verify')->assertSuccessful();

    Http::assertSent(fn ($request): bool => $request->url() === 'https://leannode.com');

    expect($company->refresh()->website)->toBe('https://leannode.com');
});

test('the command reports when there is nothing to backfill', function () {
    Company::create([
        'name' => 'SharedTech',
        'type' => 'private',
        'status' => 'approved',
        'website' => 'https://sharedtech.example',
    ]);

    $this->artisan('companies:backfill-branding')
        ->expectsOutputToContain('nothing to backfill')
        ->assertSuccessful();
});

test('deriveWebsite prefers a parenthesised Latin brand inside an Arabic name', function () {
    expect(CompanyBranding::deriveWebsite('شركة الورق العربية (Waraq)'))
        ->toBe(['website' => 'https://waraq.com', 'reason' => CompanyBranding::DERIVED]);
});

test('deriveWebsite drops corporate filler words', function () {
    expect(CompanyBranding::deriveWebsite('The Osloob Company Ltd')['website'])
        ->toBe('https://osloob.com');
});

test('deriveWebsite refuses a name that is a description rather than a brand', function () {
    expect(CompanyBranding::deriveWebsite('T2 Business Research and Development'))
        ->toBe(['website' => null, 'reason' => CompanyBranding::TOO_GENERIC]);
});

test('deriveWebsite refuses an Arabic-only name', function () {
    expect(CompanyBranding::deriveWebsite('مؤسسة وقف دعوتها'))
        ->toBe(['website' => null, 'reason' => CompanyBranding::NO_LATIN_NAME]);
});
