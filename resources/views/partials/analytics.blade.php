{{--
    Analytics (self-hosted Umami, cookieless). Included from the <head> of every
    layout; the drain partial that carries server-flashed events is a separate
    include at the end of <body>.

    `data-domains` is not decoration: without it the tracker reports from every
    host this codebase runs on — localhost, previews, a staging copy — and those
    numbers land in the same tables as the real ones with nothing to tell them
    apart. The tracker drops anything from another hostname before it leaves the
    browser. (Checked on 2026-09-18: this site's 90-day history is effectively
    clean, one stray event from 127.0.0.1. This keeps it that way.)

    Recording accepts a `$recorder` flag so a layout can opt out; see the admin
    layout for why it does.
--}}
@php($analyticsWebsiteId = 'fb84e21d-eda8-4eda-89f0-6c750b8c91fb')
{{--
    Query-string scrubber. The tracker's automatic page view sends the address
    bar verbatim, and this app puts the visitor's own search text in the address
    bar (`#[Url]` on the companies index) — so before this, every search anyone
    typed was landing in `website_event.url_query`, which has none of this app's
    access control. On a site about ratings of real workplaces, a search term
    plus a timestamp can point at a person.

    An allowlist, not a blocklist: a query parameter added later is dropped by
    default instead of silently starting a new leak. `data-exclude-search` would
    have been the one-liner, but the tracker does not parse UTM itself — it ships
    the raw query and the server extracts utm_* from it — so excluding the search
    would take attribution down with it.

    `referrer` gets the same treatment: on a wire:navigate the tracker reports the
    previous in-app URL as the referrer, which is exactly the search page.

    Defined before the deferred tracker, on `window`, and it cannot throw: the
    tracker awaits this and sends whatever comes back, so a throw here would be a
    silently broken tag.
--}}
<script data-navigate-once>
    window.umamiScrubQuery = function (type, payload) {
        try {
            var allowed = ['ref', 'gclid', 'fbclid', 'msclkid'];
            var scrub = function (value) {
                if (typeof value !== 'string' || value.indexOf('?') === -1) {
                    return value;
                }

                var url = new URL(value, window.location.href);
                var kept = new URLSearchParams();

                url.searchParams.forEach(function (paramValue, key) {
                    var name = key.toLowerCase();

                    if (name.indexOf('utm_') === 0 || allowed.indexOf(name) !== -1) {
                        kept.append(key, paramValue);
                    }
                });

                url.search = kept.toString();

                return value.indexOf('://') === -1 ? url.pathname + url.search + url.hash : url.toString();
            };

            if (payload) {
                payload.url = scrub(payload.url);
                payload.referrer = scrub(payload.referrer);
            }
        } catch (e) {
            // Never take the tag down over a URL we failed to parse.
        }

        return payload;
    };
</script>
<script defer src="https://analytics.sb.sa/script.js"
    data-website-id="{{ $analyticsWebsiteId }}"
    data-domains="{{ config('app.production_host') }}"
    data-before-send="umamiScrubQuery"></script>
{{--
    Session replay + heatmaps. A SEPARATE script, not a replacement: recorder.js
    never defines `window.umami` and posts only to /api/record, so dropping
    script.js above would silently kill every page view and custom event.
    It fetches its own config from /api/websites/<id>/recorder and does nothing
    while that says disabled, so the toggle lives in Umami, not here.

    Gated on the host in Blade because the recorder — unlike script.js above —
    has NO `data-domains` support. Server-side sampling is 1.0 (this site is
    small enough to record every session), so an ungated recorder would film
    every developer's afternoon at full rate and bury the real sessions.

    Masking is `strict` server-side, and it has to be: these pages carry
    reviewers' names, their universities, and contact details they offered to
    other students. Replays land in tables with none of this app's moderation or
    access control (same rule as App\Support\Analytics).
--}}
@if (($recorder ?? true) && request()->getHost() === config('app.production_host'))
    <script defer src="https://analytics.sb.sa/recorder.js"
        data-website-id="{{ $analyticsWebsiteId }}"></script>
@endif
