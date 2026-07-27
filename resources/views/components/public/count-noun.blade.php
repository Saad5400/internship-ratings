{{--
    Renders a count + Arabic noun with correct CLDR grammar (lang/ar/counts.php
    is the single source of truth for the plural forms). For $count >= 3 with
    animation enabled, the number animates via x-public.count-up and is
    followed by the noun-only form (e.g. "٦" + "تقييمات"); the noun is chosen
    server-side from the final count, never from the animated value. Below
    that threshold, or when $animate is false, the full static phrase is used
    so 0/1/2 read naturally ("لا توجد تقييمات" / "تقييم واحد" / "تقييمان")
    without a stray numeral.
--}}
@props([
    'count',
    'noun',
    'duration' => 700,
    'animate' => true,
])

@php
    $count = (int) $count;
@endphp

@if($count >= 3 && $animate)
    <x-public.count-up :value="$count" :duration="$duration" {{ $attributes }} />
    {{ trans_choice("counts.{$noun}_noun", $count) }}
@else
    <span {{ $attributes }}>{{ trans_choice("counts.{$noun}", $count) }}</span>
@endif
