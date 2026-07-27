@props(['label', 'value', 'description' => null, 'color' => 'primary', 'icon' => null])

@php
    $iconClasses = match ($color) {
        'success' => 'bg-green-50 text-green-600',
        'warning' => 'bg-amber-50 text-amber-600',
        'danger' => 'bg-red-50 text-red-600',
        'info' => 'bg-sky-50 text-sky-600',
        default => 'bg-blue-50 text-blue-500',
    };
@endphp

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-slate-200 bg-white p-5 shadow-xs']) }}>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="truncate text-sm font-medium text-slate-500">{{ $label }}</p>
            <p class="mt-1.5 text-2xl font-bold tabular-nums tracking-tight text-slate-900">{{ $value }}</p>
            @if($description !== null)
                <p class="mt-1 truncate text-xs text-slate-400">{{ $description }}</p>
            @endif
        </div>
        @if($icon !== null)
            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl {{ $iconClasses }}">
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
            </span>
        @endif
    </div>
</div>
