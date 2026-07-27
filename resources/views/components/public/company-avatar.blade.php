@props(['company', 'size' => 'md'])

@php
    $sizeClass = $size === 'lg' ? 'size-16' : 'size-12';
    $textSizeClass = $size === 'lg' ? 'text-2xl' : 'text-lg';
    $imgSizeClass = $size === 'lg' ? 'size-10' : 'size-8';

    $tints = [
        ['bg-sky-100 dark:bg-sky-500/15', 'text-sky-600 dark:text-sky-300'],
        ['bg-emerald-100 dark:bg-emerald-500/15', 'text-emerald-600 dark:text-emerald-300'],
        ['bg-amber-100 dark:bg-amber-500/15', 'text-amber-600 dark:text-amber-300'],
        ['bg-violet-100 dark:bg-violet-500/15', 'text-violet-600 dark:text-violet-300'],
        ['bg-rose-100 dark:bg-rose-500/15', 'text-rose-600 dark:text-rose-300'],
        ['bg-slate-100 dark:bg-slate-800', 'text-slate-600 dark:text-slate-300'],
    ];

    [$bgClass, $textClass] = $tints[crc32($company->name) % 6];
@endphp

<div {{ $attributes->class(["relative flex items-center justify-center overflow-hidden rounded-xl ring-1 ring-inset ring-slate-200 dark:ring-slate-700", $bgClass, $sizeClass]) }}>
    {{-- Exactly one of the two is ever visible. The letter is the fallback, so
         it is hidden while a favicon is present and revealed again if that
         favicon fails to load — otherwise a logo with a transparent background
         shows the initial through it. --}}
    <span @class(['font-bold', $textClass, $textSizeClass, 'hidden' => filled($company->favicon_url)])>{{ $company->initial }}</span>

    @if($company->favicon_url)
        <img
            src="{{ $company->favicon_url }}"
            alt=""
            aria-hidden="true"
            loading="lazy"
            referrerpolicy="no-referrer"
            onerror="this.previousElementSibling?.classList.remove('hidden'); this.remove()"
            class="absolute inset-0 m-auto {{ $imgSizeClass }} object-contain"
        >
    @endif
</div>
