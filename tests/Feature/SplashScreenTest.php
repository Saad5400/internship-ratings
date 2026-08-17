<?php

/**
 * The first-paint splash ships inside the public layout so it can cover the
 * screen before Vite assets and webfonts arrive. These tests keep it wired in
 * and self-contained (no external asset, self-removing).
 */
it('renders the splash as part of the public shell', function (): void {
    $this->withoutVite()
        ->get(route('companies.index'))
        ->assertOk()
        ->assertSee('id="app-splash"', false);
});

it('carries the brand mark and its sweep inside the splash', function (): void {
    $this->withoutVite()
        ->get(route('companies.index'))
        ->assertOk()
        ->assertSee('sp-brand-gradient', false)
        ->assertSee('sp-sweep-gradient', false);
});

it('guards the splash so it plays once per document load', function (): void {
    $this->withoutVite()
        ->get(route('companies.index'))
        ->assertOk()
        ->assertSee('window.__appSplashShown', false);
});
