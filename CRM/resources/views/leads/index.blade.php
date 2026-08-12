<x-layouts.app title="Leads">
    <x-ui.card>
        <x-ui.page-header
            title="Leads"
            subtitle="Tabla principal con busqueda rapida, lectura mas clara y herramientas de asignacion para operacion diaria."
        >
            <x-slot:actions>
                <x-ui.badge>{{ $leads->total() }} registros</x-ui.badge>
                <x-ui.button :href="route('leads.create')" variant="link">Crear lead</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        @if (session('status'))
            <x-ui.alert type="success">{{ session('status') }}</x-ui.alert>
        @endif

        <form method="GET" action="{{ route('leads.index') }}" style="display: grid; grid-template-columns: minmax(0, 2fr) minmax(220px, 1fr) auto; gap: 12px; margin-bottom: 20px;">
            <input type="text" name="search" value="{{ $search }}" placeholder="Buscar por nombre, telefono o email">
            <select name="status">
                <option value="">Todos los estados</option>
                @foreach ($statuses as $statusOption)
                    <option value="{{ $statusOption }}" @selected($statusOption === $status)>{{ $statusOption }}</option>
                @endforeach
            </select>
            <button class="button" type="submit">Filtrar</button>
        </form>

        @if ($canManageAssignments)
            <div class="surface" style="margin-bottom: 20px; padding: 20px;">
                <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 16px; flex-wrap: wrap;">
                    <div>
                        <h2 style="margin: 0 0 8px; font-size: 20px;">Round robin para leads sin asignar</h2>
                        <p style="margin: 0; max-width: 68ch; font-size: 14px;">
                            Toma los leads visibles del filtro actual que aun no tienen agente asignado y los distribuye entre los agentes seleccionados.
                        </p>
                    </div>
                    <x-ui.badge>Asignacion asistida</x-ui.badge>
                </div>

                <form method="POST" action="{{ route('leads.round-robin') }}" style="display: grid; gap: 14px;">
                    @csrf
                    <input type="hidden" name="search" value="{{ $search }}">
                    <input type="hidden" name="status" value="{{ $status }}">

                    <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                        @forelse ($assignableAgents as $agent)
                            <label style="display: inline-flex; align-items: center; gap: 10px; padding: 10px 14px; border: 1px solid var(--border); border-radius: 999px; background: var(--panel);">
                                <input type="checkbox" name="agent_ids[]" value="{{ $agent->id }}" style="width: auto; margin: 0;">
                                <span style="font-weight: 600;">{{ $agent->name }}</span>
                            </label>
                        @empty
                            <div class="meta">No hay agentes activos disponibles para asignacion.</div>
                        @endforelse
                    </div>

                    @error('agent_ids')
                        <div class="meta" style="color: var(--danger);">{{ $message }}</div>
                    @enderror

                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
                        <div class="meta">Selecciona dos o mas agentes para repartir el flujo de trabajo visible.</div>
                        <button class="button" type="submit" @disabled($assignableAgents->isEmpty())>Ejecutar round robin</button>
                    </div>
                </form>
            </div>
        @endif

        <div style="overflow-x: auto; border: 1px solid var(--border); border-radius: 20px; background: var(--panel);">
            <table>
                <thead>
                    <tr style="text-align: left; border-bottom: 1px solid var(--border); background: rgba(37, 99, 235, 0.04);">
                        <th style="padding: 14px 16px;">Lead</th>
                        <th style="padding: 14px 16px;">Contacto</th>
                        <th style="padding: 14px 16px;">Estado</th>
                        <th style="padding: 14px 16px;">Asignado a</th>
                        <th style="padding: 14px 16px;">Origen</th>
                        <th style="padding: 14px 16px;">Capturo</th>
                        <th style="padding: 14px 16px;">Accion rapida</th>
                        <th style="padding: 14px 16px;">Detalle</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($leads as $lead)
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td style="padding: 16px;">
                                <div style="display: grid; gap: 6px;">
                                    <strong>{{ $lead->name ?: 'Sin nombre' }}</strong>
                                    <span class="meta">{{ $lead->city ?: 'Sin ciudad' }}</span>
                                </div>
                            </td>
                            <td style="padding: 16px;">
                                <div style="display: grid; gap: 4px;">
                                    <div>{{ $lead->phone ?: 'Sin telefono' }}</div>
                                    <div class="meta">{{ $lead->email ?: 'Sin email' }}</div>
                                </div>
                            </td>
                            <td style="padding: 16px;">
                                <x-ui.badge>{{ $lead->status }}</x-ui.badge>
                            </td>
                            <td style="padding: 16px;">{{ optional($lead->assignedUser)->name ?: 'Sin asignar' }}</td>
                            <td style="padding: 16px;">{{ $lead->source ?: 'Sin origen' }}</td>
                            <td style="padding: 16px;">{{ optional($lead->creator)->name ?: 'Sistema' }}</td>
                            <td style="padding: 16px;">
                                <a href="{{ route('leads.show', $lead) }}#registrar-llamada" style="color: var(--primary-dark); font-weight: 600; text-decoration: none;">Registrar llamada</a>
                            </td>
                            <td style="padding: 16px;">
                                <a href="{{ route('leads.show', $lead) }}" style="color: var(--primary-dark); font-weight: 600; text-decoration: none;">Ver detalle</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="padding: 0;">
                                <x-ui.empty-state
                                    title="No hay leads registrados con los filtros actuales"
                                    description="Prueba con otro estado, limpia la busqueda o crea un lead manual para iniciar el flujo comercial."
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top: 18px;">
            {{ $leads->links() }}
        </div>
    </x-ui.card>
</x-layouts.app>
