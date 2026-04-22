@props(['label' => null, 'name', 'type' => 'text', 'required' => false])

<div>
    @if($label !== null)
        <label for="{{ $name }}" class="block text-sm font-medium text-slate-700 mb-1">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif
    {{ $slot }}
    @error($name)
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
