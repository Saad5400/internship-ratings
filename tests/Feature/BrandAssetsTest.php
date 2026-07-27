<?php

/**
 * The brand mark lives in two places by necessity: public/favicon.svg (and the
 * rasters beside it) for browsers and app stores, and the Blade component for
 * inline use. scripts/brand-icons.mjs renders the first set; these tests keep
 * the second from drifting away from it.
 */
function markElements(string $svg): array
{
    preg_match_all('/<(?:path|rect x)\b[^>]*>/', $svg, $matches);

    return $matches[0];
}

it('ships every icon artifact at the size its consumer expects', function (string $file, int $size): void {
    $path = public_path($file);

    expect($path)->toBeReadableFile();

    [$width, $height] = getimagesize($path);

    expect($width)->toBe($size)->and($height)->toBe($size);
})->with([
    ['apple-touch-icon.png', 180],
    ['icon-192.png', 192],
    ['icon-512.png', 512],
]);

it('ships a multi-size favicon', function (): void {
    $path = public_path('favicon.ico');

    expect($path)->toBeReadableFile();

    $header = unpack('vreserved/vtype/vcount', file_get_contents($path, length: 6));

    expect($header['reserved'])->toBe(0)
        ->and($header['type'])->toBe(1)
        ->and($header['count'])->toBe(3);
});

it('keeps the inline mark identical to the favicon', function (): void {
    $favicon = file_get_contents(public_path('favicon.svg'));
    $component = file_get_contents(resource_path('views/components/app-logo-icon.blade.php'));

    $elements = markElements($favicon);

    expect($elements)->toHaveCount(4);

    foreach ($elements as $element) {
        expect($component)->toContain($element);
    }
});

it('renders the mark in the public header', function (): void {
    $this->withoutVite()
        ->get(route('companies.index'))
        ->assertOk()
        ->assertSee('brand-mark-gradient', false);
});
