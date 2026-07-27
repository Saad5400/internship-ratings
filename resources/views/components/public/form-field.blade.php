@props(['label' => null, 'name', 'type' => 'text', 'required' => false])

<div>
    @if($label !== null)
        <label for="{{ $name }}" class="block text-sm font-medium text-slate-700 mb-1 dark:text-slate-300">
            {{ $label }}
            @if($required)
                <span class="text-red-500 dark:text-red-400">*</span>
            @endif
        </label>
    @endif
    {{ $slot }}
    @error($name)
        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</div>
