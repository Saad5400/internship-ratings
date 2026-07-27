{{--
    Arabic RTL pager for admin list pages — the single replacement for
    Livewire's stock (English/LTR) pagination view. Pass the paginator.
--}}
@props(['paginator'])

@if($paginator->hasPages())
    <nav {{ $attributes->merge(['class' => 'flex items-center justify-between gap-3']) }} aria-label="تنقّل بين الصفحات">
        <p class="text-sm text-slate-500">
            عرض {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} من {{ $paginator->total() }}
        </p>
        <div class="flex items-center gap-2">
            <button
                type="button"
                wire:click="previousPage"
                @disabled($paginator->onFirstPage())
                class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-600 shadow-xs transition-colors hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
            >
                السابق
            </button>
            <button
                type="button"
                wire:click="nextPage"
                @disabled(! $paginator->hasMorePages())
                class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-600 shadow-xs transition-colors hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
            >
                التالي
            </button>
        </div>
    </nav>
@endif
