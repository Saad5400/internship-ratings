@props(['label', 'value', 'delay' => 0])
@php $target = ($value / 5) * 100; @endphp

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
        <div class="h-full bg-blue-500 rounded-full motion-safe:transition-[width] motion-safe:duration-700 motion-safe:ease-out"
            :style="`width: ${w}%`"></div>
    </div>
    <span class="w-6 shrink-0 text-xs font-semibold text-slate-700 tabular-nums text-center">{{ $value }}</span>
</div>
