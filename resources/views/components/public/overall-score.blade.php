@props(['value'])

<div class="shrink-0 flex flex-col items-center justify-center rounded-lg bg-blue-50 px-3 py-2 min-w-14">
    <x-public.count-up :value="$value" :decimals="1" :duration="900" class="text-2xl font-bold text-blue-600 tabular-nums leading-none" />
    <span class="mt-0.5 text-[10px] font-medium text-blue-400 uppercase tracking-wide">/ 5</span>
</div>
