@php
    $loginLogoPath = base_path('Logo/Artboard 1.svg');
    $loginLogo = null;

    if (is_file($loginLogoPath)) {
        $loginLogo = file_get_contents($loginLogoPath);
        $loginLogo = preg_replace('/<\?xml.*?\?>\s*/', '', $loginLogo);
        $loginLogo = preg_replace('/<!--.*?-->\s*/s', '', $loginLogo);
    }
@endphp

<x-layouts.guest title="Acceso CRM Puerta Principal">
    <section class="login-view">
        <div class="login-brand">
            <div class="login-logo" aria-label="Puerta Principal">
                @if ($loginLogo)
                    <div class="login-logo-svg" aria-hidden="true">{!! $loginLogo !!}</div>
                @endif
            </div>

            <h1 class="login-message">Hoy es un gran dia para volver a intentarlo.</h1>
        </div>

        <div class="login-form-panel">
            <div class="login-watermark" aria-hidden="true">
                <div class="login-watermark-arch"></div>
                <div class="login-watermark-base"></div>
            </div>

            <div class="login-form-content">
                @if (session('status'))
                    <x-ui.alert type="success">{{ session('status') }}</x-ui.alert>
                @endif

                <form method="POST" action="{{ route('login.store') }}" class="auth-form">
                    @csrf

                    <div class="login-field login-field-primary">
                        <label for="email">Correo electronico</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username">
                        <p class="login-meta">Usa el correo oficial de Puerta Principal.</p>
                        @error('email')
                            <div class="meta field-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="login-field">
                        <label for="password">Contrasena</label>
                        <input id="password" name="password" type="password" required autocomplete="current-password">
                        <p class="login-meta">Tu acceso cuenta con proteccion adicional para mayor seguridad.</p>
                        @error('password')
                            <div class="meta field-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <label class="login-remember" for="remember">
                        <input id="remember" name="remember" type="checkbox" value="1" @checked(old('remember'))>
                        <span>Recordarme en este dispositivo</span>
                    </label>

                    <button class="login-submit" type="submit">Entrar al CRM</button>
                </form>

                <div class="login-help">Si no puedes entrar, solicita ayuda a un administrador.</div>
            </div>
        </div>
    </section>
</x-layouts.guest>
