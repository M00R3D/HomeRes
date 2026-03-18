@props([
    'type'        => 'success',
    'dismissible' => true,
    'class'       => '',
])
@php
    $typeClass = match ($type) {
        'error' => 'ui-alert-error',
        'warning' => 'ui-alert-warning',
        'info' => 'ui-alert-info',
        default => 'ui-alert-success',
    };
@endphp

<div {{ $attributes->class(['ui-alert', $typeClass, $class]) }}>
    <span>{{ $slot }}</span>
    @if($dismissible)
    <button onclick="this.parentElement.remove()" type="button" aria-label="Cerrar">&times;</button>
    @endif
</div>
