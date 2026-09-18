/**
 * Product analytics — the browser half of the pair with App\Support\Analytics.
 *
 * Two ways in, mirroring the two shapes an event comes in:
 *
 * - A Livewire action that stays on the page dispatches a browser event:
 *   `$this->dispatch('analytics', name: 'company_search', data: [...])`, the
 *   same convention the admin toasts use. Handled by the listener below.
 * - A flow that ends in a redirect can't wait for a listener that is about to be
 *   navigated away from, so the server flashes the event instead
 *   (App\Support\Analytics) and `partials/analytics-events` prints it into the
 *   page the redirect lands on. `drainFlashed()` picks it up from there.
 *
 * The transport is the self-hosted Umami tag in partials/analytics.blade.php.
 * It is `defer`red and freely ad-blocked, so events buffer briefly and are then
 * dropped — nothing here is ever awaited, retried forever, or allowed to throw
 * into a caller. Filling in a review must never pay for a measurement.
 *
 * **Cardinality / privacy rule.** Values are fixed words or bands, never names,
 * ids, review text, or anything typed by a person — and never the contents of a
 * rating. See the docblock on App\Support\Analytics for why.
 */

/** How long to hold events while the tag loads before concluding it never will. */
const READY_TIMEOUT_MS = 10_000;
const POLL_MS = 250;
/** Backstop against a caller in a loop; the queue is memory, not a buffer worth growing. */
const MAX_QUEUED = 25;
/** Ids already sent, so a cached back-navigation snapshot can't double-count. */
const MAX_SEEN = 100;

const queue = [];
const seen = new Set();
let polling = false;
let gaveUp = false;

function tag() {
    const umami = window.umami;

    return typeof umami?.track === 'function' ? umami : null;
}

function flush() {
    const umami = tag();

    if (!umami) {
        return false;
    }

    while (queue.length > 0) {
        const event = queue.shift();

        try {
            umami.track(event.name, event.data);
        } catch {
            // A broken or stubbed tag is not the app's problem.
        }
    }

    return true;
}

function drainWhenReady() {
    if (polling || gaveUp) {
        return;
    }

    polling = true;
    const deadline = Date.now() + READY_TIMEOUT_MS;

    const tick = () => {
        if (flush()) {
            polling = false;

            return;
        }

        if (Date.now() > deadline) {
            // Blocked, offline, or the analytics host is down. Give up for the
            // rest of the session rather than poll a phone's battery flat.
            polling = false;
            gaveUp = true;
            queue.length = 0;

            return;
        }

        window.setTimeout(tick, POLL_MS);
    };

    tick();
}

/**
 * Count one event. Never throws, never awaits, never blocks the caller.
 *
 * An event with no properties arrives from PHP as `[]`, not `{}` — an empty PHP
 * array has no other JSON shape — so anything that isn't a plain object is
 * dropped rather than handed to the tag as a list.
 */
export function track(name, data) {
    if (gaveUp || typeof name !== 'string' || queue.length >= MAX_QUEUED) {
        return;
    }

    const properties = data && typeof data === 'object' && !Array.isArray(data) ? data : undefined;

    queue.push({ name, data: properties });
    drainWhenReady();
}

/**
 * Send the events the server flashed for this page, skipping any already sent.
 * The payload is a JSON <script> rather than an inline call so there is no
 * ordering race with this deferred module — and no inline code to whitelist if
 * this app ever grows a CSP.
 */
function drainFlashed() {
    const node = document.getElementById('analytics-events');

    if (!node) {
        return;
    }

    let events;

    try {
        events = JSON.parse(node.textContent || '[]');
    } catch {
        return;
    }

    if (!Array.isArray(events)) {
        return;
    }

    for (const event of events) {
        if (typeof event?.id !== 'string' || typeof event?.name !== 'string' || seen.has(event.id)) {
            continue;
        }

        // Bounded FIFO: ids only matter for the handful of renders that can
        // still re-present the same payload, so forgetting the oldest is safe.
        if (seen.size >= MAX_SEEN) {
            seen.delete(seen.values().next().value);
        }

        seen.add(event.id);
        track(event.name, event.data);
    }
}

window.addEventListener('analytics', (event) => track(event.detail?.name, event.detail?.data));

// wire:navigate swaps the body without re-running this module, so the flashed
// payload has to be re-read per navigation. `livewire:navigated` also fires on
// the initial load; the explicit call covers a page where Livewire never boots.
document.addEventListener('livewire:navigated', drainFlashed);
drainFlashed();
