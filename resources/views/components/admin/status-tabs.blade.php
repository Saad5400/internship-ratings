{{--
    Status tabs with live count badges for list pages. `tabs` is
    [{key, label, count (nullable), color (badge color, optional)}] and
    `active` the current key; clicking calls `setTab(key)` on the page.
    Scrolls horizontally on narrow screens instead of wrapping.
--}}
@props(['tabs', 'active'])

<div {{ $attributes->merge(['class' => 'overflow-x-auto']) }}>
    <nav class="flex w-max min-w-full items-center gap-1 border-b border-slate-200" aria-label="تصفية حسب الحالة">
        @foreach($tabs as $tab)
            @php $isActive = $tab['key'] === $active; @endphp
            <button
                type="button"
                wire:click="setTab('{{ $tab['key'] }}')"
                @if($isActive) aria-current="page" @endif
                class="inline-flex items-center gap-1.5 whitespace-nowrap border-b-2 px-3 py-2.5 text-sm font-medium transition-colors {{ $isActive ? 'border-blue-500 text-blue-600' : 'border-transparent text-slate-500 hover:border-slate-200 hover:text-slate-900' }}"
            >
                {{ $tab['label'] }}
                @if(($tab['count'] ?? null) !== null)
                    <x-badge :color="$isActive ? ($tab['color'] ?? 'primary') : 'gray'">{{ $tab['count'] }}</x-badge>
                @endif
            </button>
        @endforeach
    </nav>
</div>
