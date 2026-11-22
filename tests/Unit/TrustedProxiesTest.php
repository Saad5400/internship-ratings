<?php

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * TRUSTED_PROXIES (bootstrap/app.php): which hops may set the client IP.
 *
 * Each case rebuilds the application with the variable set, because the
 * middleware reads it while the app is being configured.
 */
uses(TestCase::class);

function clientIpWithTrustedProxies(TestCase $test, ?string $trusted, string $remote, string $forwardedFor): string
{
    if ($trusted === null) {
        unset($_ENV['TRUSTED_PROXIES'], $_SERVER['TRUSTED_PROXIES']);
    } else {
        $_ENV['TRUSTED_PROXIES'] = $_SERVER['TRUSTED_PROXIES'] = $trusted;
    }

    try {
        (fn () => $this->refreshApplication())->call($test);
        Route::get('/_test/client-ip', fn () => request()->ip());

        return $test->withServerVariables(['REMOTE_ADDR' => $remote])
            ->withHeader('X-Forwarded-For', $forwardedFor)
            ->get('/_test/client-ip')
            ->assertOk()
            ->getContent();
    } finally {
        unset($_ENV['TRUSTED_PROXIES'], $_SERVER['TRUSTED_PROXIES']);
    }
}

test('blank keeps trusting every proxy, as before', function () {
    expect(clientIpWithTrustedProxies($this, null, '10.0.1.5', '203.0.113.7'))->toBe('203.0.113.7');
    expect(clientIpWithTrustedProxies($this, '*', '10.0.1.5', '203.0.113.7'))->toBe('203.0.113.7');
});

test('a listed chain (Cloudflare -> Traefik) resolves to the real visitor', function () {
    // Traefik (10.0.1.5) appended the Cloudflare edge (173.245.48.10) to the
    // visitor's address; both hops are trusted, so the visitor is the client.
    $ip = clientIpWithTrustedProxies($this, '10.0.0.0/8, 173.245.48.0/20', '10.0.1.5', '203.0.113.7, 173.245.48.10');

    expect($ip)->toBe('203.0.113.7');
});

test('a forwarded header from an untrusted peer is ignored', function () {
    $ip = clientIpWithTrustedProxies($this, '10.0.0.0/8', '198.51.100.9', '203.0.113.7');

    expect($ip)->toBe('198.51.100.9');
});

test('a spoofed hop in front of the trusted chain cannot pick the client IP', function () {
    // The visitor sent "X-Forwarded-For: 1.2.3.4" themselves; Cloudflare then
    // appended their real address. Only the right-most untrusted hop counts.
    $ip = clientIpWithTrustedProxies($this, '10.0.0.0/8,173.245.48.0/20', '10.0.1.5', '1.2.3.4, 203.0.113.7, 173.245.48.10');

    expect($ip)->toBe('203.0.113.7');
});
