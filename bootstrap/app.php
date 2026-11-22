<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Trusted proxies: trusting '*' lets any client spoof X-Forwarded-For,
        // and behind Cloudflare -> Traefik only the edge would be trusted, so
        // request()->ip() would be a Cloudflare address and per-IP throttling
        // would lump every visitor together. Pin the real proxies via
        // TRUSTED_PROXIES (comma-separated IPs/CIDRs: the Traefik network plus
        // Cloudflare's ranges). Unset/blank or '*' keeps the previous
        // trust-everything behaviour so local/dev setups keep working.
        $trustedProxies = trim((string) env('TRUSTED_PROXIES', ''));
        $middleware->trustProxies(
            at: $trustedProxies === '' || $trustedProxies === '*'
                ? '*'
                : array_values(array_filter(array_map('trim', explode(',', $trustedProxies))))
        );

        /*
         * One combined call on purpose: `redirectUsersTo()` after
         * `redirectGuestsTo()` silently overwrites the guest redirect with a
         * `fn () => null` (Middleware::redirectTo wraps its null $guests
         * default into a truthy closure and re-registers it).
         */
        $middleware->redirectTo(
            guests: fn (): string => route('login'),
            users: fn (): string => route('admin.dashboard'),
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
