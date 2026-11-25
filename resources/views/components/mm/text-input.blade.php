@props([
    'disabled' => false,
    'value' => null,
])

<input
    @disabled($disabled)
    value="{{ old($attributes->get('name'), $value) }}"
    {{ $attributes->merge(['class' => 'mm-input']) }}
>
