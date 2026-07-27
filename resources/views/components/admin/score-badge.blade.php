{{--
    Overall-rating badge for admin surfaces. The single source of truth for the
    admin score thresholds (≥4 good, ≥3 acceptable, otherwise poor) that the
    Filament tables and infolists used to repeat inline.
--}}
@props(['score', 'size' => 'sm'])

@php
    $color = match (true) {
        $score === null => 'gray',
        $score >= 4 => 'success',
        $score >= 3 => 'warning',
        default => 'danger',
    };
@endphp

<x-badge :color="$color" {{ $attributes->merge(['class' => $size === 'lg' ? '!px-3 !py-1 !text-sm font-semibold tabular-nums' : 'tabular-nums']) }}>
    @if($score === null)
        لا يوجد
    @else
        {{ number_format((float) $score, 1) }} / 5
    @endif
</x-badge>
