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

<div
    class="relative"
    x-data="{ open: false, query: '' }"
    @keydown.escape.window="open = false"
>
    <button
        type="button"
        @click="open = ! open; query = ''"
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
        @click.outside="open = false"
        x-transition.opacity.duration.150ms
        class="absolute top-full start-0 z-30 mt-2 w-64 rounded-xl border border-slate-200 bg-white p-2 shadow-lg dark:border-slate-800 dark:bg-slate-900"
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
