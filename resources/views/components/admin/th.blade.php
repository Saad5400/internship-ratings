{{--
    Table header cell. With `field`, it becomes a sortable header: the
    consuming Livewire page must expose `sortBy(string $field)` plus public
    `$sortField` / `$sortDirection` ('asc'|'desc') passed in as `sort` and
    `direction`.
--}}
@props(['field' => null, 'sort' => null, 'direction' => 'desc', 'align' => 'start'])

@php
    $isActive = $field !== null && $sort === $field;
@endphp

<th scope="col" {{ $attributes->merge(['class' => 'whitespace-nowrap px-4 py-3 font-semibold text-'.$align]) }}>
    @if($field === null)
        {{ $slot }}
    @else
        <button
            type="button"
            wire:click="sortBy('{{ $field }}')"
            class="group inline-flex items-center gap-1 transition-colors hover:text-slate-900 {{ $isActive ? 'text-slate-900' : '' }}"
            @if($isActive) aria-sort="{{ $direction === 'asc' ? 'ascending' : 'descending' }}" @endif
        >
            {{ $slot }}
            <svg class="size-3.5 transition-all {{ $isActive ? '' : 'opacity-0 group-hover:opacity-40' }} {{ $isActive && $direction === 'asc' ? 'rotate-180' : '' }}"
                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
            </svg>
        </button>
    @endif
</th>
