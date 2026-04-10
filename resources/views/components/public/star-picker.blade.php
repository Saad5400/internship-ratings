@props([
    'field',
    'label',
    'value' => null,
    'required' => false,
])

<div>
    <label class="mb-2 block text-sm font-medium text-slate-700">
        {{ $label }} @if($required) <span class="text-red-500">*</span> @endif
    </label>
    <div class="flex items-center gap-1" x-data="{
        value: @entangle($field).live,
        hover: 0,
    }">
        @for($i = 1; $i <= 5; $i++)
            <button
                type="button"
                @click="value = {{ $i }}"
                @mouseenter="hover = {{ $i }}"
                @mouseleave="hover = 0"
                class="p-1 transition-transform hover:scale-110 active:scale-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/30 rounded"
                aria-label="{{ $i }} من 5">
                <svg
                    :class="((hover || value) >= {{ $i }}) ? 'text-amber-400' : 'text-slate-200'"
                    class="size-8 transition-colors duration-150"
                    fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.196-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                </svg>
            </button>
        @endfor
        <span class="ms-3 text-sm font-semibold text-slate-700 tabular-nums" x-show="value" x-cloak>
            <span x-text="value"></span>/5
        </span>
    </div>
    @error($field) <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
</div>
