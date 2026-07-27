@props(['title', 'subtitle' => null])

<div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
    <div>
        <h1 class="text-3xl font-bold tracking-tight text-slate-900">{{ $title }}</h1>
        @if($subtitle !== null)
            <p class="mt-2 text-slate-500">{{ $subtitle }}</p>
        @endif
    </div>
    @if(trim($slot) !== '')
        <div class="flex shrink-0 items-center gap-2">
            {{ $slot }}
        </div>
    @endif
</div>
