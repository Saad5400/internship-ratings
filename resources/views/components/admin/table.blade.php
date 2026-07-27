{{--
    Data-table shell for admin list pages. Wide content scrolls inside this
    container — the page body never scrolls horizontally. Pass thead rows via
    the `head` slot and tbody rows via the default slot.
--}}
@props([])

<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs']) }}>
    <div class="overflow-x-auto">
        <table class="w-full min-w-max text-sm">
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
