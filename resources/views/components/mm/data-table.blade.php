@props([
    'headers' => [],
    'empty' => 'No records found.',
])

<div class="mm-table-wrap">
    <table class="mm-table">
        @if (count($headers))
            <thead>
                <tr>
                    @foreach ($headers as $header)
                        <th scope="col">{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
        @endif
        <tbody>
            {{ $slot }}
        </tbody>
    </table>

    @isset($emptyState)
        <div class="px-4 py-8 text-center text-sm text-slate-500">
            {{ $emptyState }}
        </div>
    @endisset
</div>
