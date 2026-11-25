@props(['value' => 0])

@php($value = max(0, min(100, (int) $value)))

<div {{ $attributes->class(['mm-progress-linear']) }} role="progressbar" aria-valuenow="{{ $value }}" aria-valuemin="0" aria-valuemax="100">
    <span style="width: {{ $value }}%"></span>
</div>
