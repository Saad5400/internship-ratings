@props(['status'])

<x-badge :color="\App\Support\ModerationStatus::color($status)" {{ $attributes }}>
    {{ \App\Support\ModerationStatus::label($status) }}
</x-badge>
