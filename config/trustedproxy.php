<?php

/*
|--------------------------------------------------------------------------
| Trusted Proxies
|--------------------------------------------------------------------------
|
| Which hops may tell us the client's address via X-Forwarded-*. Laravel's
| TrustProxies middleware reads `trustedproxy.proxies` on every request.
|
| Trusting '*' means trusting only the immediate peer (Traefik), so behind
| Cloudflare -> Traefik request()->ip() would be a Cloudflare edge address
| and per-IP throttling would lump every visitor together. Production lists
| the real proxies in TRUSTED_PROXIES (comma-separated IPs/CIDRs: the Traefik
| network plus Cloudflare's ranges). Unset/blank or '*' keeps the previous
| trust-the-peer behaviour so local/dev setups keep working.
|
| A config key rather than env() in bootstrap/app.php: it survives
| `config:cache`, and tests can set it without rebuilding the application.
|
*/

$trustedProxies = trim((string) env('TRUSTED_PROXIES', ''));

return [

    'proxies' => $trustedProxies === '' || $trustedProxies === '*'
        ? '*'
        : array_values(array_filter(array_map('trim', explode(',', $trustedProxies)))),

];
