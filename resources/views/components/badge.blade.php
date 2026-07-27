{{--
    Shared pill badge. The single source of truth mapping the app's semantic
    color vocabulary (success / warning / danger / info / primary / gray — as
    returned by ModerationStatus::color() and the enums' color()) to Tailwind
    classes. Every badge surface (status, recommendation, modality, scores)
    renders through this component instead of re-hardcoding pill styles.
--}}
@props(['color' => 'gray'])

@php
    $classes = match ($color) {
        'success' => 'bg-green-100 text-green-700 dark:bg-emerald-500/15 dark:text-emerald-300',
        'warning' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300',
        'danger' => 'bg-red-100 text-red-700 dark:bg-rose-500/15 dark:text-rose-300',
        'info' => 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300',
        'primary' => 'bg-blue-100 text-blue-700 dark:bg-blue-500/15 dark:text-blue-300',
        default => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-medium {$classes}"]) }}>
    {{ $slot }}
</span>
