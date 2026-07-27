@props([
    'value',
    'compact' => false,
])

@php
    [$containerClass, $numberClass, $suffixClass] = match (true) {
        $value >= 4 => ['bg-emerald-50 text-emerald-700 ring-emerald-600/15 dark:bg-emerald-950/40 dark:text-emerald-300 dark:ring-emerald-500/20', 'text-emerald-700 dark:text-emerald-300', 'text-emerald-500 dark:text-emerald-400'],
        $value >= 3 => ['bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-950/40 dark:text-amber-300 dark:ring-amber-500/20', 'text-amber-700 dark:text-amber-300', 'text-amber-500 dark:text-amber-400'],
        default => ['bg-rose-50 text-rose-600 ring-rose-600/15 dark:bg-rose-950/40 dark:text-rose-300 dark:ring-rose-500/20', 'text-rose-600 dark:text-rose-300', 'text-rose-400 dark:text-rose-400'],
    };

    $sizeClass = $compact
        ? 'min-w-16 rounded-xl px-3.5 py-2.5'
        : 'min-w-14 rounded-lg px-3 py-2';
@endphp

<div {{ $attributes->class("shrink-0 flex flex-col items-center justify-center ring-1 ring-inset {$containerClass} {$sizeClass}") }}>
    <x-public.count-up :value="$value" :decimals="1" :duration="900" class="text-2xl font-bold tabular-nums leading-none {{ $numberClass }}" />
    <span class="mt-0.5 text-[10px] font-medium uppercase tracking-wide {{ $suffixClass }}">/ 5</span>
</div>
