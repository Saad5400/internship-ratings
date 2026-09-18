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
<script defer src="https://analytics.sb.sa/script.js"
    data-website-id="{{ $analyticsWebsiteId }}"
    data-domains="{{ config('app.production_host') }}"></script>
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
