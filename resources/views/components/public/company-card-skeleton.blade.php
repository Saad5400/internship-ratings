@props(['count' => 2])

@for($i = 0; $i < $count; $i++)
    <div class="animate-pulse rounded-xl border border-slate-200/80 bg-white p-6 shadow-xs" aria-hidden="true">
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0 flex-1 space-y-2">
                {{-- title (text-lg, ~28px line) --}}
                <div class="h-5 w-2/3 rounded bg-slate-200"></div>
                {{-- description: 2 lines text-sm --}}
                <div class="space-y-1.5 pt-1">
                    <div class="h-3.5 w-full rounded bg-slate-100"></div>
                    <div class="h-3.5 w-4/5 rounded bg-slate-100"></div>
                </div>
            </div>
            {{-- overall-score block: min-w-14 rounded-lg px-3 py-2 with text-2xl + tiny /5 --}}
            <div class="flex shrink-0 flex-col items-center justify-center gap-1 rounded-lg bg-slate-100 px-3 py-2 ring-1 ring-inset ring-slate-200">
                <div class="h-6 w-7 rounded bg-slate-200"></div>
                <div class="h-2 w-5 rounded bg-slate-200"></div>
            </div>
        </div>
        {{-- footer text-xs row --}}
        <div class="mt-5 flex items-center gap-3">
            <div class="h-3 w-24 rounded bg-slate-100"></div>
            <div class="size-1 rounded-full bg-slate-200"></div>
            <div class="h-3 w-28 rounded bg-slate-100"></div>
        </div>
    </div>
@endfor
