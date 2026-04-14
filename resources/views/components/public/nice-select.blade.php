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
])

<x-public.form-field :label="$label" :name="$name" :required="$required">
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
        @if($single && $clearable)
            @scope('selection', $option)
                <span class="inline-flex items-center gap-1.5">
                    <span>{{ data_get($option, 'name') }}</span>
                    <button
                        type="button"
                        @click.stop="reset()"
                        class="inline-flex shrink-0 rounded-full text-slate-400 transition-colors hover:text-red-600"
                        aria-label="مسح الاختيار"
                    >
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </span>
            @endscope
        @endif

        {{ $slot }}
    </x-mary-choices>
</x-public.form-field>
