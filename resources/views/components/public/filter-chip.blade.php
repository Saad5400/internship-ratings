@props([
    'facet',
    'label',
    'options' => [],
    'selected' => [],
    'searchable' => false,
])

@php
    /**
     * The chip always names its facet, then folds the selection into the same
     * pill — "المدينة" / "المدينة: الرياض" / "المدينة: 3 مختارة" — so a filtered
     * state is readable without opening the popover.
     */
    $selectedCount = count($selected);
    $firstLabel = $selectedCount === 1
        ? (collect($options)->firstWhere('value', $selected[0])['label'] ?? null)
        : null;

    $summary = match (true) {
        $selectedCount === 0 => $label,
        $firstLabel !== null => $label.': '.$firstLabel,
        default => $label.': '.$selectedCount.' مختارة',
    };
@endphp

{{-- The panel hangs off the chip (`start-0`, i.e. its right edge in RTL) and
     extends toward the viewport's left. A chip sitting near that edge — the last
     one on a wrapped row, which on mobile is most of them — would push the panel
     past the boundary and make the whole page scroll sideways. `shift` nudges the
     panel's inline-start inset until it sits inside, instead of flipping the anchor:
     flipping only helps a chip with room on the other side, a clamp is correct everywhere.
     The measurement runs on open (and on resize / chip re-layout); until Alpine
     boots, `x-cloak` keeps the panel display:none, so there is nothing to overflow. --}}
<div
    class="relative"
    x-data="{
        open: false,
        query: '',
        shift: 0,
        reposition() {
            if (! this.open || ! this.$refs.panel) { return; }

            const panel = this.$refs.panel;
            const gutter = 8;
            const rtl = getComputedStyle(panel).direction === 'rtl';

            // Drop the previous correction before measuring, so what we read is the
            // panel's natural position — never a guess about what is already applied.
            panel.style.insetInlineStart = '';

            // Measure against the page box rather than the viewport: an absolutely
            // positioned overflow can leave the document scrolled sideways, and the
            // body rect scrolls with the panel, so this stays right either way.
            const page = document.body.getBoundingClientRect();
            const rect = panel.getBoundingClientRect();
            const left = rect.left - page.left;
            const right = rect.right - page.left;

            const correction = left < gutter
                ? gutter - left
                : Math.min(0, (page.width - gutter) - right);

            this.shift = Math.round(rtl ? -correction : correction);
            panel.style.insetInlineStart = this.shift ? this.shift + 'px' : '';
        },
        toggle() {
            this.open = ! this.open;
            this.query = '';
            this.$nextTick(() => this.reposition());
        },
    }"
    {{-- Re-measure when the chip itself changes shape: ResizeObserver for reflow,
         MutationObserver for a Livewire morph (applying a filter grows the chip's
         label, which can move the panel). Only content is observed, never
         attributes, so writing the shift back cannot feed itself. --}}
    x-init="
        new ResizeObserver(() => reposition()).observe($el);
        new MutationObserver(() => reposition()).observe($el, { childList: true, subtree: true, characterData: true });
    "
    @keydown.escape.window="open = false"
    @resize.window="reposition()"
>
    <button
        type="button"
        @click="toggle()"
        :aria-expanded="open ? 'true' : 'false'"
        class="inline-flex max-w-full items-center gap-1.5 rounded-full border px-3.5 py-2 text-sm font-medium transition-colors
            @if($selectedCount > 0)
                border-blue-500 bg-blue-50 text-blue-700 dark:border-blue-500 dark:bg-blue-950/50 dark:text-blue-300
            @else
                border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:text-slate-900 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-400 dark:hover:text-slate-100
            @endif"
    >
        <span class="min-w-0 truncate">{{ $summary }}</span>
        <svg class="size-4 shrink-0 transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    <div
        x-show="open"
        x-cloak
        x-ref="panel"
        @click.outside="open = false"
        x-transition.opacity.duration.150ms
        {{-- Inline-axis inset, not `transform` — x-transition owns the transform property. --}}
        :style="{ insetInlineStart: shift ? shift + 'px' : '' }"
        class="absolute top-full start-0 z-30 mt-2 w-64 max-w-[calc(100vw-2rem)] rounded-xl border border-slate-200 bg-white p-2 shadow-lg dark:border-slate-800 dark:bg-slate-900"
        role="group"
        aria-label="{{ $label }}"
    >
        @if($searchable)
            <input
                type="text"
                x-model="query"
                placeholder="ابحث في {{ $label }}..."
                class="mb-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 placeholder-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100 dark:placeholder-slate-500"
                aria-label="ابحث في {{ $label }}"
            />
        @endif

        <div class="max-h-64 overflow-y-auto">
            @foreach($options as $option)
                <label
                    wire:key="facet-{{ $facet }}-option-{{ md5($option['value']) }}"
                    data-label="{{ $option['label'] }}"
                    x-show="query === '' || $el.dataset.label.includes(query)"
                    class="flex cursor-pointer items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm transition-colors hover:bg-slate-50 dark:hover:bg-slate-800"
                >
                    <input
                        type="checkbox"
                        wire:click="toggleFilter('{{ $facet }}', @js($option['value']))"
                        @checked(in_array($option['value'], $selected, true))
                        class="size-4 shrink-0 rounded border-slate-300 text-blue-600 focus:ring-blue-500/30 dark:border-slate-700 dark:bg-slate-950"
                    />
                    <span class="min-w-0 flex-1 truncate text-slate-700 dark:text-slate-300">{{ $option['label'] }}</span>
                    <span class="shrink-0 text-xs tabular-nums text-slate-400 dark:text-slate-500">{{ $option['count'] }}</span>
                </label>
            @endforeach

            @if($searchable)
                <p
                    x-show="query !== '' && $el.parentElement.querySelectorAll('label:not([style*=none])').length === 0"
                    x-cloak
                    class="px-2.5 py-3 text-center text-sm text-slate-400 dark:text-slate-500"
                >لا توجد نتائج</p>
            @endif
        </div>

        @if($selectedCount > 0)
            <button
                type="button"
                wire:click="clearFacet('{{ $facet }}')"
                class="mt-1 w-full rounded-lg px-2.5 py-2 text-start text-sm font-medium text-blue-600 transition-colors hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-950/50"
            >مسح {{ $label }}</button>
        @endif
    </div>
</div>
