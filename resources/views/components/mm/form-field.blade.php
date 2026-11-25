@props([
    'label' => null,
    'name' => null,
    'hint' => null,
    'required' => false,
])

<div {{ $attributes->class(['mm-form-field']) }}>
    @if ($label)
        <label @if($name) for="{{ $name }}" @endif>
            {{ $label }}
            @if ($required)
                <span class="text-red-600" aria-hidden="true">*</span>
            @endif
        </label>
    @endif

    {{ $slot }}

    @if ($hint)
        <p class="text-xs text-slate-500">{{ $hint }}</p>
    @endif

    @if ($name)
        <x-input-error :messages="$errors->get($name)" class="mt-1" />
    @endif
</div>
