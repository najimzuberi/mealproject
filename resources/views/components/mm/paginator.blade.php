@props(['paginator'])

@if ($paginator->hasPages())
    <div class="flex items-center justify-between gap-3 py-3 text-sm text-slate-600">
        <p>
            Showing
            <span class="font-medium text-slate-900">{{ $paginator->firstItem() }}</span>
            to
            <span class="font-medium text-slate-900">{{ $paginator->lastItem() }}</span>
            of
            <span class="font-medium text-slate-900">{{ $paginator->total() }}</span>
        </p>
        <div>
            {{ $paginator->withQueryString()->links() }}
        </div>
    </div>
@endif
