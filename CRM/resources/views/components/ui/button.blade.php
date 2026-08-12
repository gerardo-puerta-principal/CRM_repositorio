@props([
    'href' => null,
    'variant' => 'primary',
])

@php
    $classes = $variant === 'link' ? ['button-link'] : ['button'];
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>
        {{ $slot }}
    </a>
@else
    <button {{ $attributes->class($classes) }}>
        {{ $slot }}
    </button>
@endif
