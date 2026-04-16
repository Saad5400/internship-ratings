@props([
    'label',
    'name',
    'options' => [],
    'placeholder' => null,
    'required' => false,
    'searchFunction' => 'search',
    'noResultText' => 'لا توجد نتائج',
    'debounce' => '250ms',
    'single' => true,
    'searchable' => false,
    'clearable' => true,
    'offline' => false,
])

@php
    $resolvedClearable = $single ? false : $clearable;
@endphp

<x-public.form-field :label="$label" :name="$name" :required="$required">
    @if($offline)
        <x-mary-choices-offline
            {{ $attributes }}
            :options="$options"
            :placeholder="$placeholder"
            :no-result-text="$noResultText"
            :debounce="$debounce"
            :single="$single"
            :searchable="$searchable"
            :clearable="$resolvedClearable"
            omit-error
        >
            @if($single)
                @scope('selection', $option)
                    <span class="inline-flex items-center gap-1.5">
                        <span>{{ data_get($option, 'name') }}</span>
                        <button
                            type="button"
                            onclick="event.preventDefault(); event.stopPropagation(); Alpine.$data(this.closest('[x-data]')).reset();"
                            class="-me-0.5 inline-flex size-4 items-center justify-center rounded-full text-current opacity-60 transition-opacity hover:opacity-100"
                            aria-label="مسح الاختيار"
                        >
                            <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </span>
                @endscope
            @endif
            {{ $slot }}
        </x-mary-choices-offline>
    @else
        <x-mary-choices
            {{ $attributes }}
            :options="$options"
            :search-function="$searchFunction"
            :placeholder="$placeholder"
            :no-result-text="$noResultText"
            :debounce="$debounce"
            :single="$single"
            :searchable="$searchable"
            :clearable="$resolvedClearable"
            omit-error
        >
            @if($single)
                @scope('selection', $option)
                    <span class="inline-flex items-center gap-1.5">
                        <span>{{ data_get($option, 'name') }}</span>
                        <button
                            type="button"
                            onclick="event.preventDefault(); event.stopPropagation(); Alpine.$data(this.closest('[x-data]')).reset();"
                            class="-me-0.5 inline-flex size-4 items-center justify-center rounded-full text-current opacity-60 transition-opacity hover:opacity-100"
                            aria-label="مسح الاختيار"
                        >
                            <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </span>
                @endscope
            @endif
            {{ $slot }}
        </x-mary-choices>
    @endif
</x-public.form-field>
