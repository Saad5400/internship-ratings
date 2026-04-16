@props(['label', 'value', 'delay' => 0])

@php
    $target = ($value / 5) * 100;

    [$fillClass, $valueClass] = match (true) {
        $value >= 4 => ['bg-sky-600', 'text-sky-700'],
        $value >= 3 => ['bg-slate-500', 'text-slate-700'],
        default => ['bg-slate-300', 'text-slate-500'],
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
    <span class="w-24 shrink-0 text-xs font-medium text-slate-600">{{ $label }}</span>
    <div class="flex-1 h-1.5 bg-slate-100 rounded-full overflow-hidden" role="progressbar" aria-valuenow="{{ $value }}" aria-valuemin="1" aria-valuemax="5" aria-label="{{ $label }}">
        <div class="h-full rounded-full motion-safe:transition-[width] motion-safe:duration-700 motion-safe:ease-out {{ $fillClass }}"
            :style="`width: ${w}%`"></div>
    </div>
    <span class="w-6 shrink-0 text-xs font-semibold tabular-nums text-center {{ $valueClass }}">{{ $value }}</span>
</div>
