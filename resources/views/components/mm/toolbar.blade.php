<div {{ $attributes->class(['mm-toolbar']) }}>
    <div class="min-w-0">
        @isset($title)
            <h1 class="truncate text-xl font-semibold text-slate-900" style="font-family: var(--mm-font-display)">
                {{ $title }}
            </h1>
        @endisset
        @isset($subtitle)
            <p class="text-sm text-slate-500">{{ $subtitle }}</p>
        @endisset
    </div>
    <div class="flex flex-wrap items-center gap-2">
        {{ $slot }}
    </div>
</div>
