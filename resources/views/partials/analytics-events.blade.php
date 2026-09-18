{{--
    Events the server flashed for this render (App\Support\Analytics), handed to
    resources/js/analytics.js. Data, not code: a JSON <script> can't race the
    deferred module that reads it, and there is no inline call to whitelist if
    this app ever grows a CSP.

    It sits at the end of <body> because wire:navigate replaces the body on every
    navigation — a payload in <head> would not be refreshed there.
--}}
@if (filled(session('analytics')))
    <script type="application/json" id="analytics-events">@json(session('analytics'))</script>
@endif
