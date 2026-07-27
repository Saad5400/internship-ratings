{{--
    Consequence-count confirm dialog (destructive-action ladder, rung 2).
    The trigger slot opens the dialog; the confirm button runs `wireClick`
    on the consuming Livewire component. Always state what will happen in
    `message` — counts included — never a generic "are you sure".
--}}
@props(['title', 'message', 'confirmLabel' => 'تأكيد', 'wireClick'])

<div x-data="{ open: false }" class="inline-flex">
    <span class="inline-flex" @click="open = true">
        {{ $trigger }}
    </span>

    <template x-teleport="body">
        {{--
            No Alpine x-transition here on purpose: class-based transitions
            silently break x-show on x-teleport'ed content (the effect never
            fires). CSS animations replay when display toggles, so the
            fade/rise below run on every open.
        --}}
        <div
            x-show="open"
            x-cloak
            @keydown.escape.window="open = false"
            class="fixed inset-0 z-50 flex items-end justify-center p-4 motion-safe:animate-overlay-in sm:items-center"
            role="dialog"
            aria-modal="true"
            aria-label="{{ $title }}"
        >
            <div @click="open = false" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm"></div>

            <div class="relative w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-xl motion-safe:animate-dialog-in">
                <div class="flex items-start gap-3">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-red-50 text-red-600">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </span>
                    <div class="min-w-0">
                        <h3 class="font-bold text-slate-900">{{ $title }}</h3>
                        <p class="mt-1 text-sm leading-relaxed text-slate-600">{{ $message }}</p>
                    </div>
                </div>

                <div class="mt-5 flex items-center justify-end gap-2">
                    <button type="button" @click="open = false"
                        class="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-100">
                        إلغاء
                    </button>
                    <button type="button" wire:click="{{ $wireClick }}" @click="open = false"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-xs transition-all hover:bg-red-700 active:scale-[0.98]">
                        {{ $confirmLabel }}
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>
