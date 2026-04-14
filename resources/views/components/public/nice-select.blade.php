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
            :clearable="$single ? false : $clearable"
            omit-error
        >
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
            :clearable="$single ? false : $clearable"
            omit-error
        >
            {{ $slot }}
        </x-mary-choices>
    @endif
</x-public.form-field>
