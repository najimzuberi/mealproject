@props([
    'disabled' => false,
])

<select @disabled($disabled) {{ $attributes->merge(['class' => 'mm-input']) }}>
    {{ $slot }}
</select>
