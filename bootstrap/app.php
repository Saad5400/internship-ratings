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
        $middleware->trustProxies(at: '*');

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
