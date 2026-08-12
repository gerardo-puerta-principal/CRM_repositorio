<x-layouts.app title="Usuarios">
    <x-ui.card>
        <x-ui.page-header
            title="Usuarios"
            subtitle="Administración simple de cuentas, roles, supervisores y acceso operativo del equipo."
        >
            <x-slot:actions>
                <x-ui.badge>{{ $users->count() }} usuarios</x-ui.badge>
                <x-ui.button :href="route('users.create')" variant="link">Crear usuario</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        @if (session('status'))
            <x-ui.alert type="success">{{ session('status') }}</x-ui.alert>
        @endif

        <div style="overflow-x: auto; border: 1px solid var(--border); border-radius: 20px; background: var(--panel);">
            <table>
                <thead>
                    <tr style="text-align: left; border-bottom: 1px solid var(--border); background: rgba(37, 99, 235, 0.04);">
                        <th style="padding: 14px 16px;">Usuario</th>
                        <th style="padding: 14px 16px;">Rol</th>
                        <th style="padding: 14px 16px;">Supervisor</th>
                        <th style="padding: 14px 16px;">Estado</th>
                        <th style="padding: 14px 16px;">Último acceso</th>
                        <th style="padding: 14px 16px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $userModel)
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td style="padding: 16px;">
                                <div style="display: grid; gap: 6px;">
                                    <strong>{{ $userModel->name }}</strong>
                                    <span class="meta">{{ $userModel->email }}</span>
                                </div>
                            </td>
                            <td style="padding: 16px;">
                                <x-ui.badge>{{ $userModel->role }}</x-ui.badge>
                            </td>
                            <td style="padding: 16px;">{{ $userModel->supervisor?->name ?: 'Sin supervisor' }}</td>
                            <td style="padding: 16px;">
                                <x-ui.badge>{{ $userModel->is_active ? 'Activo' : 'Inactivo' }}</x-ui.badge>
                            </td>
                            <td style="padding: 16px;">{{ $userModel->last_login_at?->format('Y-m-d H:i') ?: 'Sin registro' }}</td>
                            <td style="padding: 16px;">
                                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                                    <a href="{{ route('users.edit', $userModel) }}" style="color: var(--primary-dark); font-weight: 600; text-decoration: none;">Editar</a>
                                    @if (auth()->id() !== $userModel->id && $userModel->role !== \App\Models\User::ROLE_SUPER_ADMIN && $userModel->is_active)
                                        <form method="POST" action="{{ route('users.impersonate', $userModel) }}">
                                            @csrf
                                            <button type="submit" title="Entrar como {{ $userModel->name }}" aria-label="Entrar como {{ $userModel->name }}" style="border: 0; background: transparent; color: var(--primary-dark); font-weight: 600; cursor: pointer; padding: 0;">Entrar</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding: 0;">
                                <x-ui.empty-state
                                    title="No hay usuarios registrados"
                                    description="Crea supervisores y agentes para comenzar a distribuir leads y operar el CRM con equipos reales."
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
</x-layouts.app>
