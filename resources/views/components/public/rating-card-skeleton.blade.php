@props(['count' => 2])

@for($i = 0; $i < $count; $i++)
    <div class="animate-pulse rounded-xl border border-slate-200/80 bg-white p-6 shadow-xs" aria-hidden="true">
        {{-- Header: title + meta chips + compact score --}}
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0 flex-1">
                <div class="h-4 w-1/2 rounded bg-slate-200"></div>
                <div class="mt-2 flex flex-wrap items-center gap-1.5">
                    <div class="h-5 w-16 rounded-md bg-slate-100"></div>
                    <div class="h-5 w-20 rounded-md bg-slate-100"></div>
                    <div class="h-5 w-14 rounded-md bg-slate-100"></div>
                    <div class="h-5 w-12 rounded-md bg-blue-50"></div>
                </div>
            </div>
            {{-- compact overall-score: min-w-16 rounded-xl px-3.5 py-2.5 --}}
            <div class="flex shrink-0 flex-col items-center justify-center gap-1 rounded-xl bg-slate-100 px-3.5 py-2.5 ring-1 ring-inset ring-slate-200">
                <div class="h-6 w-8 rounded bg-slate-200"></div>
                <div class="h-2 w-5 rounded bg-slate-200"></div>
            </div>
        </div>

        {{-- Facts row: stipend + chips (rounded-full px-2.5 py-1) --}}
        <div class="mt-4 flex flex-wrap items-center gap-2">
            <div class="h-7 w-32 rounded-full bg-sky-50 ring-1 ring-inset ring-sky-600/20"></div>
            <div class="h-7 w-24 rounded-full bg-blue-50 ring-1 ring-inset ring-blue-600/20"></div>
            <div class="h-7 w-28 rounded-full bg-blue-50 ring-1 ring-inset ring-blue-600/20"></div>
        </div>

        {{-- Multi-criteria bars --}}
        <div class="mt-5 space-y-2.5">
            @foreach([72, 60, 84, 56, 68] as $width)
                <div class="flex items-center gap-3">
                    <div class="h-3 w-20 rounded bg-slate-100"></div>
                    <div class="h-2 flex-1 rounded-full bg-slate-100">
                        <div class="h-2 rounded-full bg-slate-200" style="width: {{ $width }}%"></div>
                    </div>
                    <div class="h-3 w-6 rounded bg-slate-100"></div>
                </div>
            @endforeach
        </div>

        {{-- Review text: 3 lines, leading-relaxed --}}
        <div class="mt-5 space-y-2">
            <div class="h-3.5 w-full rounded bg-slate-100"></div>
            <div class="h-3.5 w-11/12 rounded bg-slate-100"></div>
            <div class="h-3.5 w-3/4 rounded bg-slate-100"></div>
        </div>

        {{-- Pros / cons grid --}}
        <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div class="rounded-lg rounded-s-none border-s-2 border-s-sky-200 bg-sky-50/60 px-3 py-2">
                <div class="h-2.5 w-12 rounded bg-sky-200/70"></div>
                <div class="mt-2 h-3 w-5/6 rounded bg-slate-200/70"></div>
            </div>
            <div class="rounded-lg rounded-s-none border-s-2 border-s-slate-300 bg-slate-100/60 px-3 py-2">
                <div class="h-2.5 w-12 rounded bg-slate-300/70"></div>
                <div class="mt-2 h-3 w-5/6 rounded bg-slate-200/70"></div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-4">
            <div class="h-6 w-24 rounded-full bg-slate-100 ring-1 ring-inset ring-slate-200"></div>
            <div class="flex items-center gap-3">
                <div class="h-3 w-28 rounded bg-slate-100"></div>
                <div class="h-3 w-16 rounded bg-slate-100"></div>
            </div>
        </div>
    </div>
@endfor
