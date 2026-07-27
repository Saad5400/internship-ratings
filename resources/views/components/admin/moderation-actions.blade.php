{{--
    One-click approve / reject for a moderatable record. The consuming Livewire
    page must use App\Livewire\Concerns\ModeratesRecords. Actions that would be
    a no-op for the current status are hidden rather than disabled.

    Pass `confirmReject` (a consequence sentence, counts included) to gate the
    reject behind a confirm dialog — used when rejecting cascades, e.g. hiding
    a company that has ratings. Plain status flips stay one-click + undo toast.
--}}
@props(['record', 'type', 'confirmReject' => null])

@php
    $rejectClasses = 'inline-flex items-center gap-1.5 rounded-lg border border-red-200 bg-white px-3 py-2 text-sm font-medium text-red-600 shadow-xs transition-all hover:bg-red-50 active:scale-[0.98] disabled:opacity-60';
    $rejectWireClick = "moderate('{$type}', {$record->id}, 'rejected')";
@endphp

<div {{ $attributes->merge(['class' => 'flex items-center gap-2']) }}>
    @if($record->status !== 'approved')
        <button
            type="button"
            wire:click="moderate('{{ $type }}', {{ $record->id }}, 'approved')"
            wire:loading.attr="disabled"
            wire:target="moderate('{{ $type }}', {{ $record->id }}, 'approved')"
            class="inline-flex items-center gap-1.5 rounded-lg bg-green-600 px-3 py-2 text-sm font-medium text-white shadow-xs transition-all hover:bg-green-700 active:scale-[0.98] disabled:opacity-60"
        >
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            موافقة
        </button>
    @endif

    @if($record->status !== 'rejected')
        @if($confirmReject !== null)
            <x-admin.confirm-dialog
                title="رفض مع تبعات"
                :message="$confirmReject"
                confirm-label="رفض على أي حال"
                :wire-click="$rejectWireClick"
            >
                <x-slot:trigger>
                    <button type="button" class="{{ $rejectClasses }}">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        رفض
                    </button>
                </x-slot:trigger>
            </x-admin.confirm-dialog>
        @else
            <button
                type="button"
                wire:click="moderate('{{ $type }}', {{ $record->id }}, 'rejected')"
                wire:loading.attr="disabled"
                wire:target="moderate('{{ $type }}', {{ $record->id }}, 'rejected')"
                class="{{ $rejectClasses }}"
            >
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                رفض
            </button>
        @endif
    @endif

    {{ $slot }}
</div>
