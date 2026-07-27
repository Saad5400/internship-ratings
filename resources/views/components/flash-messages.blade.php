{{--
    Session flash alerts (success / error), auto-dismissing. Shared by the
    public and admin layouts — the single source of truth for flash styling.
--}}
@if(session('success'))
    <div role="status"
        x-data="{ show: true }"
        x-init="setTimeout(() => show = false, 6000)"
        x-show="show"
        x-transition:enter="motion-safe:transition motion-safe:ease-out motion-safe:duration-300"
        x-transition:enter-start="motion-safe:opacity-0 motion-safe:-translate-y-2"
        x-transition:enter-end="motion-safe:opacity-100 motion-safe:translate-y-0"
        x-transition:leave="motion-safe:transition motion-safe:ease-in motion-safe:duration-200"
        x-transition:leave-start="motion-safe:opacity-100"
        x-transition:leave-end="motion-safe:opacity-0"
        class="mb-6 flex items-start gap-3 rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-800">
        <svg class="size-5 shrink-0 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span class="flex-1">{{ session('success') }}</span>
        <button type="button" @click="show = false" class="shrink-0 rounded text-green-600/70 transition-colors hover:text-green-800" aria-label="إغلاق">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
@endif

@if(session('error'))
    <div role="alert"
        x-data="{ show: true }"
        x-init="setTimeout(() => show = false, 8000)"
        x-show="show"
        x-transition:enter="motion-safe:transition motion-safe:ease-out motion-safe:duration-300"
        x-transition:enter-start="motion-safe:opacity-0 motion-safe:-translate-y-2"
        x-transition:enter-end="motion-safe:opacity-100 motion-safe:translate-y-0"
        x-transition:leave="motion-safe:transition motion-safe:ease-in motion-safe:duration-200"
        x-transition:leave-start="motion-safe:opacity-100"
        x-transition:leave-end="motion-safe:opacity-0"
        class="mb-6 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
        <svg class="size-5 shrink-0 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        <span class="flex-1">{{ session('error') }}</span>
        <button type="button" @click="show = false" class="shrink-0 rounded text-red-600/70 transition-colors hover:text-red-800" aria-label="إغلاق">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
@endif
