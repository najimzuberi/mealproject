@props([
    'disabled' => false,
    'value' => null,
])

<textarea @disabled($disabled) {{ $attributes->merge(['class' => 'mm-input min-h-24']) }}>{{ old($attributes->get('name'), $value) }}</textarea>
