@props([
    'title' => '',
    'subtitle' => null,
    'eyebrow' => null,
])

<section {{ $attributes->class('auth-panel') }}>
    <div class="auth-panel-header">
        @if ($eyebrow)
            <div>
                <span class="badge">{{ $eyebrow }}</span>
            </div>
        @endif

        <div>
            <h1>{{ $title }}</h1>
            @if ($subtitle)
                <p class="auth-panel-copy">{{ $subtitle }}</p>
            @endif
        </div>
    </div>

    <div class="auth-panel-card">
        {{ $slot }}
    </div>
</section>
