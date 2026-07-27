@props(['label', 'value', 'delay' => 0])

@php
    $target = ($value / 5) * 100;

    [$fillClass, $valueClass] = match (true) {
        $value >= 4 => ['bg-emerald-600', 'text-emerald-700 dark:text-emerald-300'],
        $value >= 3 => ['bg-amber-500', 'text-amber-700 dark:text-amber-300'],
        default => ['bg-rose-400', 'text-rose-600 dark:text-rose-400'],
    };
@endphp

<div class="flex items-center gap-3"
    x-data="{
        w: 0,
        start() {
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                this.w = {{ $target }};
                return;
            }
            setTimeout(() => { this.w = {{ $target }}; }, {{ (int) $delay }});
        }
    }"
    x-init="$nextTick(() => start())">
    <span class="w-24 shrink-0 text-xs font-medium text-slate-600 dark:text-slate-400">{{ $label }}</span>
    <div class="flex-1 h-1.5 bg-slate-100 rounded-full overflow-hidden dark:bg-slate-800" role="progressbar" aria-valuenow="{{ $value }}" aria-valuemin="1" aria-valuemax="5" aria-label="{{ $label }}">
        <div class="h-full rounded-full motion-safe:transition-[width] motion-safe:duration-700 motion-safe:ease-out {{ $fillClass }}"
            :style="`width: ${w}%`"></div>
    </div>
    <span class="w-6 shrink-0 text-xs font-semibold tabular-nums text-center {{ $valueClass }}">{{ $value }}</span>
</div>
