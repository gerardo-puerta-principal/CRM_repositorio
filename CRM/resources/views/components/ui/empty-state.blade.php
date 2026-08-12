@props([
    'title' => 'Sin datos por mostrar',
    'description' => null,
])

<div {{ $attributes->class(['empty-state']) }}>
    <div>
        <h3 style="margin: 0 0 8px; font-size: 18px;">{{ $title }}</h3>
        @if ($description)
            <p style="margin: 0;">{{ $description }}</p>
        @endif
        {{ $slot }}
    </div>
</div>
