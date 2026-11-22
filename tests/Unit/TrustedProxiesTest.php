<?php

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Trusted proxies (config/trustedproxy.php, TRUSTED_PROXIES): which hops may
 * set the client IP.
 *
 * The middleware reads `trustedproxy.proxies` per request, so each case sets
 * the config directly; nothing here depends on what `.env` holds.
 */
uses(TestCase::class);

function clientIpBehind(TestCase $test, string|array $proxies, string $remote, string $forwardedFor): string
{
    config(['trustedproxy.proxies' => $proxies]);
    Route::get('/_test/client-ip', fn () => request()->ip());

    return $test->withServerVariables(['REMOTE_ADDR' => $remote])
        ->withHeader('X-Forwarded-For', $forwardedFor)
        ->get('/_test/client-ip')
        ->assertOk()
        ->getContent();
}

/** The config file evaluated with TRUSTED_PROXIES set to $value (null = unset). */
function trustedProxiesConfigFor(?string $value): mixed
{
    $saved = [$_ENV['TRUSTED_PROXIES'] ?? null, $_SERVER['TRUSTED_PROXIES'] ?? null, getenv('TRUSTED_PROXIES')];

    try {
        if ($value === null) {
            unset($_ENV['TRUSTED_PROXIES'], $_SERVER['TRUSTED_PROXIES']);
            putenv('TRUSTED_PROXIES');
        } else {
            $_ENV['TRUSTED_PROXIES'] = $_SERVER['TRUSTED_PROXIES'] = $value;
            putenv("TRUSTED_PROXIES={$value}");
        }

        return (require config_path('trustedproxy.php'))['proxies'];
    } finally {
        [$env, $server, $put] = $saved;
        unset($_ENV['TRUSTED_PROXIES'], $_SERVER['TRUSTED_PROXIES']);
        if ($env !== null) {
            $_ENV['TRUSTED_PROXIES'] = $env;
        }
        if ($server !== null) {
            $_SERVER['TRUSTED_PROXIES'] = $server;
        }
        putenv($put === false ? 'TRUSTED_PROXIES' : "TRUSTED_PROXIES={$put}");
    }
}

test('TRUSTED_PROXIES blank, unset or * keeps trusting the peer; a list is trimmed and split', function () {
    expect(trustedProxiesConfigFor(null))->toBe('*');
    expect(trustedProxiesConfigFor(''))->toBe('*');
    expect(trustedProxiesConfigFor(' * '))->toBe('*');
    expect(trustedProxiesConfigFor('10.0.0.0/8, 173.245.48.0/20,'))->toBe(['10.0.0.0/8', '173.245.48.0/20']);
});

test('trusting the peer (*) keeps the previous behaviour', function () {
    expect(clientIpBehind($this, '*', '10.0.1.5', '203.0.113.7'))->toBe('203.0.113.7');
});

test('a listed chain (Cloudflare -> Traefik) resolves to the real visitor', function () {
    // Traefik (10.0.1.5) appended the Cloudflare edge (173.245.48.10) to the
    // visitor's address; both hops are trusted, so the visitor is the client.
    $ip = clientIpBehind($this, ['10.0.0.0/8', '173.245.48.0/20'], '10.0.1.5', '203.0.113.7, 173.245.48.10');

    expect($ip)->toBe('203.0.113.7');
});

test('a forwarded header from an untrusted peer is ignored', function () {
    $ip = clientIpBehind($this, ['10.0.0.0/8'], '198.51.100.9', '203.0.113.7');

    expect($ip)->toBe('198.51.100.9');
});

test('a spoofed hop in front of the trusted chain cannot pick the client IP', function () {
    // The visitor sent "X-Forwarded-For: 1.2.3.4" themselves; Cloudflare then
    // appended their real address. Only the right-most untrusted hop counts.
    $ip = clientIpBehind($this, ['10.0.0.0/8', '173.245.48.0/20'], '10.0.1.5', '1.2.3.4, 203.0.113.7, 173.245.48.10');

    expect($ip)->toBe('203.0.113.7');
});
