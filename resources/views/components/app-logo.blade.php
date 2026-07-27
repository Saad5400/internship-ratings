@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand name="تقييم التدريب" {{ $attributes }}>
        <x-slot name="logo">
            <x-app-logo-icon class="size-8 rounded-lg" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="تقييم التدريب" {{ $attributes }}>
        <x-slot name="logo">
            <x-app-logo-icon class="size-8 rounded-lg" />
        </x-slot>
    </flux:brand>
@endif
