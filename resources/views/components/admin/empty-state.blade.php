{{--
    Teaching empty state: an icon, a reason, and (via the slot) a next step —
    never a bare void. Mirrors the public companies-index empty state.
--}}
@props(['icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'message'])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-dashed border-slate-200 bg-white py-14 text-center']) }}>
    <svg class="mx-auto size-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
    <p class="mt-4 text-sm text-slate-500">{{ $message }}</p>
    @if(trim($slot) !== '')
        <div class="mt-3">
            {{ $slot }}
        </div>
    @endif
</div>
