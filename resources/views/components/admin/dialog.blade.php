{{--
    Generic dialog for light-object forms (two-tier creation: light objects in
    a dialog, heavy flows on a page). Bottom-sheet on mobile, centered on
    desktop. The `trigger` slot opens it; the Livewire page closes it after a
    successful action with: $this->dispatch('close-dialog', id: '<id>').

    No Alpine x-transition inside x-teleport (it breaks x-show) — entrance
    animates via the CSS animate-overlay-in / animate-dialog-in utilities.
--}}
@props(['id', 'title', 'wide' => false])

<div
    x-data="{ open: false }"
    @close-dialog.window="if ($event.detail.id === '{{ $id }}') open = false"
    @open-dialog.window="if ($event.detail.id === '{{ $id }}') open = true"
    class="inline-flex"
>
    @isset($trigger)
        <span class="inline-flex" @click="open = true">
            {{ $trigger }}
        </span>
    @endisset

    <template x-teleport="body">
        <div
            x-show="open"
            x-cloak
            @keydown.escape.window="open = false"
            class="fixed inset-0 z-50 flex items-end justify-center p-0 motion-safe:animate-overlay-in sm:items-center sm:p-4"
            role="dialog"
            aria-modal="true"
            aria-label="{{ $title }}"
        >
            <div @click="open = false" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm"></div>

            <div class="relative max-h-[92vh] w-full {{ $wide ? 'sm:max-w-2xl' : 'sm:max-w-md' }} overflow-y-auto rounded-t-2xl border border-slate-200 bg-white p-5 shadow-xl sm:rounded-2xl sm:p-6 motion-safe:animate-dialog-in">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h3 class="text-lg font-bold text-slate-900">{{ $title }}</h3>
                    <button type="button" @click="open = false" class="rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600" aria-label="إغلاق">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{ $slot }}
            </div>
        </div>
    </template>
</div>
