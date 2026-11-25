@props(['show' => false, 'label' => 'Working…'])

<div
    x-data="{ show: @js($show) }"
    x-show="show"
    x-cloak
    class="fixed inset-0 z-40 flex items-center justify-center bg-slate-900/30 backdrop-blur-[1px]"
    role="alert"
    aria-busy="true"
>
    <div class="rounded-lg bg-white px-6 py-4 shadow-lg">
        <x-mm.spinner :label="$label" />
    </div>
</div>
