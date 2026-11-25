@props([
    'name',
    'title' => null,
    'maxWidth' => '2xl',
])

<x-modal :name="$name" :show="false" :maxWidth="$maxWidth">
    <div class="p-6">
        @if ($title)
            <h2 class="text-lg font-semibold text-slate-900" style="font-family: var(--mm-font-display)">
                {{ $title }}
            </h2>
        @endif

        <div class="mt-4">
            {{ $slot }}
        </div>

        @isset($footer)
            <div class="mt-6 flex justify-end gap-3">
                {{ $footer }}
            </div>
        @endisset
    </div>
</x-modal>
