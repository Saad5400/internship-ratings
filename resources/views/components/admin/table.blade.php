{{--
    Data-table shell for admin list pages. Wide content scrolls inside this
    container — the page body never scrolls horizontally. Pass thead rows via
    the `head` slot and tbody rows via the default slot.
--}}
@props([])

<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs']) }}>
    <div class="overflow-x-auto">
        {{--
            `relative` is what keeps the promise above. A `min-w-max` table is
            far wider than a phone, so an absolutely positioned descendant —
            `sr-only` on an actions header, most obviously — sits hundreds of
            pixels outside the viewport. Without a positioned ancestor its
            containing block is the initial one, so the scroller above cannot
            clip it and that offset lands on the *document* as scrollable
            overflow: the page itself goes wide and every sticky/fixed element
            (topbar, mobile tab bar) detaches. Positioning the table hands those
            descendants a containing block inside the scroller.
        --}}
        <table class="relative w-full min-w-max text-sm">
            <thead>
                <tr class="border-b border-slate-100 bg-slate-50/60 text-start text-xs text-slate-500">
                    {{ $head }}
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                {{ $slot }}
            </tbody>
        </table>
    </div>
</div>
