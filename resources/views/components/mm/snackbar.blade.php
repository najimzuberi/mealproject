@props([
    'type' => 'info',
])

@php
    $colors = match ($type) {
        'success' => 'bg-emerald-800',
        'error' => 'bg-red-800',
        'warning' => 'bg-amber-800',
        default => 'bg-slate-900',
    };
@endphp

<div
    x-data="{ show: true }"
    x-show="show"
    x-init="setTimeout(() => show = false, 4000)"
    x-transition
    {{ $attributes->class(['mm-snackbar', $colors]) }}
    role="status"
>
    {{ $slot }}
</div>
