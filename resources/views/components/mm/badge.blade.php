@props(['tone' => 'neutral'])

@php
    $class = match ($tone) {
        'success' => 'mm-badge mm-badge-success',
        'warning' => 'mm-badge mm-badge-warning',
        'danger' => 'mm-badge mm-badge-danger',
        default => 'mm-badge mm-badge-neutral',
    };
@endphp

<span {{ $attributes->class([$class]) }}>{{ $slot }}</span>
