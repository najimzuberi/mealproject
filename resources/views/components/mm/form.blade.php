{{--
  Shared reusable form shell used across Meal Master modules.
  Pass fields via slot; actions via $actions.
--}}
@props([
    'method' => 'POST',
    'action' => null,
    'enctype' => null,
])

@php
    $spoof = strtoupper($method) !== 'GET' && strtoupper($method) !== 'POST';
    $formMethod = $spoof ? 'POST' : strtoupper($method);
@endphp

<form
    method="{{ $formMethod }}"
    @if($action) action="{{ $action }}" @endif
    @if($enctype) enctype="{{ $enctype }}" @endif
    {{ $attributes->class(['space-y-5']) }}
>
    @csrf
    @if ($spoof)
        @method($method)
    @endif

    <div class="space-y-4">
        {{ $slot }}
    </div>

    @isset($actions)
        <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 pt-4">
            {{ $actions }}
        </div>
    @endisset
</form>
