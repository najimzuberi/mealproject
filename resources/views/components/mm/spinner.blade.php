@props(['label' => 'Loading'])

<span {{ $attributes->class(['inline-flex items-center gap-2 text-sm text-slate-600']) }} role="status" aria-live="polite">
    <span class="mm-spinner" aria-hidden="true"></span>
    <span>{{ $label }}</span>
</span>
