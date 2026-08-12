@props([
    'title' => '',
    'subtitle' => null,
])

<div {{ $attributes->class(['page-header']) }}>
    <div>
        <h1>{{ $title }}</h1>
        @if ($subtitle)
            <p>{{ $subtitle }}</p>
        @endif
    </div>

    @if (isset($actions))
        <div class="page-header-actions">
            {{ $actions }}
        </div>
    @endif
</div>
