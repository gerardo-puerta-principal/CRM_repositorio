<x-layouts.app title="Editar usuario">
    <x-ui.card>
        <x-ui.page-header
            title="Editar usuario"
            subtitle="Gestion de rol, supervisor, estado y seguridad basica para la cuenta seleccionada."
        >
            <x-slot:actions>
                @if (auth()->id() !== $userModel->id && $userModel->role !== \App\Models\User::ROLE_SUPER_ADMIN && $userModel->is_active)
                    <form method="POST" action="{{ route('users.impersonate', $userModel) }}">
                        @csrf
                        <button class="button" type="submit">Entrar a cuenta</button>
                    </form>
                @endif
            </x-slot:actions>
        </x-ui.page-header>

        @if (session('status'))
            <x-ui.alert type="success">{{ session('status') }}</x-ui.alert>
        @endif

        <form method="POST" action="{{ route('users.update', $userModel) }}" style="display: grid; gap: 20px;">
            @csrf
            @method('PUT')
            @include('users._form', ['buttonLabel' => 'Guardar cambios'])
        </form>
    </x-ui.card>

    <div style="height: 16px;"></div>

    <x-ui.card>
        <x-ui.page-header
            title="Reset manual de contrasena"
            subtitle="Actualiza credenciales cuando el usuario no pueda acceder o requiera un nuevo inicio seguro."
        />

        <form method="POST" action="{{ route('users.reset-password', $userModel) }}" style="display: grid; gap: 18px;">
            @csrf
            <div class="grid">
                <div class="field">
                    <label for="password">Nueva contrasena</label>
                    <input id="password" name="password" type="password" placeholder="Nueva contrasena">
                    @error('password')
                        <div class="meta" style="color: var(--danger);">{{ $message }}</div>
                    @enderror
                </div>
                <div class="field">
                    <label for="password_confirmation">Confirmar contrasena</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" placeholder="Confirmar contrasena">
                </div>
            </div>
            <div class="actions">
                <span class="meta">Comparte la nueva contrasena por un canal seguro y solicita cambio posterior si aplica.</span>
                <button class="button" type="submit">Actualizar contrasena</button>
            </div>
        </form>
    </x-ui.card>
</x-layouts.app>
