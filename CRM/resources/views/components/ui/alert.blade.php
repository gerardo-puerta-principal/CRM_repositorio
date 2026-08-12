@props([
    'type' => 'success',
])

@php
    $typeClass = match ($type) {
        'danger', 'error' => 'alert-danger',
        default => 'alert-success',
    };
@endphp

<div {{ $attributes->class(['alert', $typeClass]) }}>
    {{ $slot }}
</div>
