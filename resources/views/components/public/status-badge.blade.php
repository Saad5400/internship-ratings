@props(['status'])

@php
    $styles = match($status) {
        'approved' => 'bg-green-100 text-green-700',
        'rejected' => 'bg-red-100 text-red-700',
        'pending' => 'bg-amber-100 text-amber-700',
        default => 'bg-slate-100 text-slate-600',
    };
    $labels = match($status) {
        'approved' => 'موافق عليه',
        'rejected' => 'مرفوض',
        'pending' => 'قيد المراجعة',
        default => $status,
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {$styles}"]) }}>
    {{ $labels }}
</span>
