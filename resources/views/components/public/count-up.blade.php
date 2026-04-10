@props([
    'value',
    'decimals' => 0,
    'duration' => 800,
])

@php
    $target = (float) $value;
    $formatted = number_format($target, $decimals);
@endphp

<span {{ $attributes }}
    x-data="{
        n: 0,
        run() {
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                this.n = {{ $target }};
                return;
            }
            const target = {{ $target }};
            const duration = {{ $duration }};
            const start = performance.now();
            const step = (t) => {
                const p = Math.min((t - start) / duration, 1);
                const eased = 1 - Math.pow(1 - p, 3);
                this.n = target * eased;
                if (p < 1) requestAnimationFrame(step);
                else this.n = target;
            };
            requestAnimationFrame(step);
        }
    }"
    x-init="$nextTick(() => run())"
    x-text="n.toFixed({{ (int) $decimals }})"
>{{ $formatted }}</span>
