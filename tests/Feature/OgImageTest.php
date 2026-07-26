<?php

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
