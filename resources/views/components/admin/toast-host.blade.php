{{--
    The panel's single toast outlet. Livewire components dispatch
    `$this->dispatch('toast', message: ..., type: 'success'|'danger', undoEvent: ?, undoPayload: ?)`
    and this host renders them; an optional undo action dispatches the given
    Livewire event back to whichever page component listens (#[On]).
    Toasts are reserved for action outcomes — never passive state changes.
--}}
<div
    x-data="{
        toasts: [],
        nextId: 1,
        add(detail) {
            const toast = {
                id: this.nextId++,
                message: detail.message ?? '',
                type: detail.type ?? 'success',
                undoEvent: detail.undoEvent ?? null,
                undoPayload: detail.undoPayload ?? null,
            };
            this.toasts.push(toast);
            setTimeout(() => this.remove(toast.id), toast.undoEvent ? 8000 : 4000);
        },
        remove(id) {
            this.toasts = this.toasts.filter(t => t.id !== id);
        },
        undo(toast) {
            if (toast.undoEvent) {
                Livewire.dispatch(toast.undoEvent, toast.undoPayload ?? {});
            }
            this.remove(toast.id);
        },
    }"
    @toast.window="add($event.detail)"
    class="pointer-events-none fixed inset-x-0 bottom-20 z-50 flex flex-col items-center gap-2 px-4 md:bottom-6"
    aria-live="polite"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-transition:enter="motion-safe:transition motion-safe:ease-out motion-safe:duration-200"
            x-transition:enter-start="motion-safe:opacity-0 motion-safe:translate-y-2"
            x-transition:enter-end="motion-safe:opacity-100 motion-safe:translate-y-0"
            x-transition:leave="motion-safe:transition motion-safe:ease-in motion-safe:duration-150"
            x-transition:leave-start="motion-safe:opacity-100"
            x-transition:leave-end="motion-safe:opacity-0"
            class="pointer-events-auto flex items-center gap-3 rounded-xl border bg-white px-4 py-3 text-sm shadow-lg"
            :class="toast.type === 'danger' ? 'border-red-200 text-red-800' : 'border-slate-200 text-slate-800'"
        >
            <svg x-show="toast.type === 'success'" class="size-5 shrink-0 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <svg x-show="toast.type === 'danger'" class="size-5 shrink-0 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
            <span x-text="toast.message"></span>
            <button
                x-show="toast.undoEvent"
                type="button"
                @click="undo(toast)"
                class="shrink-0 rounded-lg px-2 py-1 text-sm font-semibold text-blue-600 transition-colors hover:bg-blue-50"
            >تراجع</button>
            <button type="button" @click="remove(toast.id)" class="shrink-0 rounded text-slate-400 transition-colors hover:text-slate-600" aria-label="إغلاق">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </template>
</div>
