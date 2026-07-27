{{--
    Password input with a reveal toggle. The wrapper pins dir="ltr" so the
    input's logical padding and the absolutely-positioned eye resolve in the
    SAME direction context — on an RTL page, an ltr input's `pe-*` is right
    padding while a parent-positioned `end-0` overlay lands on the left,
    which is how the eye ends up sitting on top of the text. Keeping the
    whole widget LTR puts the eye on the right, clear of the text.

    Bind with wire:model via attributes; pass `autocomplete` as appropriate
    (current-password / new-password).
--}}
@props(['id' => null, 'autocomplete' => 'current-password', 'required' => false, 'placeholder' => null])

<div x-data="{ show: false }" dir="ltr" class="relative">
    <input
        :type="show ? 'text' : 'password'"
        @if($id) id="{{ $id }}" @endif
        @required($required)
        autocomplete="{{ $autocomplete }}"
        @if($placeholder) placeholder="{{ $placeholder }}" @endif
        {{ $attributes->merge(['class' => 'w-full rounded-xl border border-slate-200 bg-white ps-4 pe-11 py-3 text-sm text-slate-900 placeholder-slate-400 shadow-xs transition-all focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none']) }}
    />
    <button
        type="button"
        @click="show = !show"
        tabindex="-1"
        class="absolute inset-y-0 end-0 flex items-center pe-3.5 text-slate-400 transition-colors hover:text-slate-600"
        :aria-label="show ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور'"
        :aria-pressed="show"
    >
        <svg x-show="!show" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
        <svg x-show="show" x-cloak class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
    </button>
</div>
