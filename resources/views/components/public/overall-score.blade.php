@props([
    'value',
    'compact' => false,
])

@php
    [$containerClass, $numberClass, $suffixClass] = match (true) {
        $value >= 4 => ['bg-sky-50 text-sky-700 ring-sky-600/15', 'text-sky-700', 'text-sky-500'],
        $value >= 3 => ['bg-slate-100 text-slate-700 ring-slate-300', 'text-slate-700', 'text-slate-500'],
        default => ['bg-slate-50 text-slate-500 ring-slate-200', 'text-slate-500', 'text-slate-400'],
    };

    $sizeClass = $compact
        ? 'min-w-16 rounded-xl px-3.5 py-2.5'
        : 'min-w-14 rounded-lg px-3 py-2';
@endphp

<div {{ $attributes->class("shrink-0 flex flex-col items-center justify-center ring-1 ring-inset {$containerClass} {$sizeClass}") }}>
    <x-public.count-up :value="$value" :decimals="1" :duration="900" class="text-2xl font-bold tabular-nums leading-none {{ $numberClass }}" />
    <span class="mt-0.5 text-[10px] font-medium uppercase tracking-wide {{ $suffixClass }}">/ 5</span>
</div>
