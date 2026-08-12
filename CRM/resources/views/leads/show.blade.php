<x-layouts.app title="Detalle lead">
    <x-ui.card>
        <x-ui.page-header
            :title="$lead->name ?: 'Sin nombre'"
            subtitle="Gestion operativa del lead con cambio de estado, llamadas, recordatorios e historial consolidado."
        >
            <x-slot:actions>
                <x-ui.badge>{{ $lead->status }}</x-ui.badge>
                @if (request()->user()?->isSuperAdmin())
                    <form
                        method="POST"
                        action="{{ route('leads.destroy', $lead) }}"
                        onsubmit="return confirm('Se ocultara este lead de la operacion normal, pero conservara su informacion en base de datos. Deseas continuar?');"
                    >
                        @csrf
                        @method('DELETE')
                        <x-ui.button
                            type="submit"
                            style="background: rgba(190, 24, 93, 0.14); color: #be185d; border: 1px solid rgba(190, 24, 93, 0.22);"
                        >
                            Eliminar lead
                        </x-ui.button>
                    </form>
                @endif
                <x-ui.button :href="route('leads.index')" variant="link">Volver</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        @if (session('status'))
            <x-ui.alert type="success">{{ session('status') }}</x-ui.alert>
        @endif

        <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px;">
            <div class="surface" style="padding: 16px 18px;">
                <strong>Telefono</strong>
                <div class="meta" style="margin-top: 8px;">{{ $lead->phone ?: 'Sin telefono' }}</div>
            </div>
            <div class="surface" style="padding: 16px 18px;">
                <strong>Email</strong>
                <div class="meta" style="margin-top: 8px;">{{ $lead->email ?: 'Sin email' }}</div>
            </div>
            <div class="surface" style="padding: 16px 18px;">
                <strong>Ciudad</strong>
                <div class="meta" style="margin-top: 8px;">{{ $lead->city ?: 'Sin ciudad' }}</div>
            </div>
            <div class="surface" style="padding: 16px 18px;">
                <strong>Tipo</strong>
                <div class="meta" style="margin-top: 8px;">{{ $lead->type ?: 'Sin tipo' }}</div>
            </div>
            <div class="surface" style="padding: 16px 18px;">
                <strong>Origen</strong>
                <div class="meta" style="margin-top: 8px;">{{ $lead->source ?: 'Sin origen' }}</div>
            </div>
            <div class="surface" style="padding: 16px 18px;">
                <strong>Estado actual</strong>
                <div class="meta" style="margin-top: 8px;">{{ $lead->status }}</div>
            </div>
            <div class="surface" style="padding: 16px 18px;">
                <strong>Capturo</strong>
                <div class="meta" style="margin-top: 8px;">{{ optional($lead->creator)->name ?: 'Sistema' }}</div>
            </div>
            <div class="surface" style="padding: 16px 18px;">
                <strong>Asignado a</strong>
                <div class="meta" style="margin-top: 8px;">{{ optional($lead->assignedUser)->name ?: 'Sin asignar' }}</div>
            </div>
            <div class="surface" style="padding: 16px 18px;">
                <strong>Creado</strong>
                <div class="meta" style="margin-top: 8px;">{{ $lead->created_at?->format('Y-m-d H:i') ?: 'Sin fecha' }}</div>
            </div>
            <div class="surface" style="padding: 16px 18px;">
                <strong>Actualizado</strong>
                <div class="meta" style="margin-top: 8px;">{{ $lead->updated_at?->format('Y-m-d H:i') ?: 'Sin fecha' }}</div>
            </div>
            <div class="surface" style="padding: 16px 18px;">
                <strong>Ultimo contacto</strong>
                <div class="meta" style="margin-top: 8px;">{{ $lead->last_contact_at?->format('Y-m-d H:i') ?: 'Sin registro' }}</div>
            </div>
            <div class="surface" style="padding: 16px 18px;">
                <strong>Proximo recordatorio</strong>
                @if ($nextPendingReminder)
                    <div class="meta" style="margin-top: 8px;">{{ $nextPendingReminder->scheduled_at?->format('Y-m-d H:i') ?: 'Sin fecha' }}</div>
                    <div style="margin-top: 6px; font-size: 14px; line-height: 1.6;">{{ $nextPendingReminder->description }}</div>
                @else
                    <div class="meta" style="margin-top: 8px;">Sin recordatorios pendientes</div>
                @endif
            </div>
        </div>
    </x-ui.card>

    @if ($canManageAssignments)
        <div style="height: 16px;"></div>
        <x-ui.card>
            <x-ui.page-header
                title="Asignacion"
                subtitle="Define el agente responsable o deja el lead sin asignar mientras se distribuye por supervisor."
            />
            <form method="POST" action="{{ route('leads.assign', $lead) }}" style="display: flex; align-items: end; gap: 12px; flex-wrap: wrap;">
                @csrf
                <div style="min-width: 280px; flex: 1;">
                    <label for="assigned_user_id" style="display: block; margin-bottom: 6px; font-weight: 700;">Agente asignado</label>
                    <select id="assigned_user_id" name="assigned_user_id">
                        <option value="">Sin asignar</option>
                        @foreach ($assignableAgents as $agent)
                            <option value="{{ $agent->id }}" @selected((int) old('assigned_user_id', $lead->assigned_user_id) === $agent->id)>
                                {{ $agent->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('assigned_user_id')
                        <div class="meta" style="margin-top: 6px; color: var(--danger);">{{ $message }}</div>
                    @enderror
                </div>
                <button class="button" type="submit">Guardar asignacion</button>
            </form>
        </x-ui.card>
    @endif

    <div style="height: 16px;"></div>

    <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px;">
        <x-ui.card>
            <x-ui.page-header
                title="Cambiar estado"
                subtitle="Actualiza el punto actual del pipeline y registra el contexto operativo."
            />
            <form method="POST" action="{{ route('leads.status', $lead) }}" style="display: grid; gap: 12px;">
                @csrf
                <div class="field">
                    <label for="status">Nuevo estado</label>
                    <select id="status" name="status">
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" @selected(old('status', $lead->status) === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                    @error('status')
                        <div class="meta" style="color: var(--danger);">{{ $message }}</div>
                    @enderror
                </div>
                <div class="field">
                    <label for="status_note">Nota</label>
                    <textarea id="status_note" name="note" rows="4">{{ old('note') }}</textarea>
                    <div class="meta">Obligatoria si el estado cambia a Contactado.</div>
                    @error('status_note')
                        <div class="meta" style="color: var(--danger);">{{ $message }}</div>
                    @enderror
                </div>
                <div>
                    <button class="button" type="submit">Guardar estado</button>
                </div>
            </form>
        </x-ui.card>

        <x-ui.card id="registrar-llamada">
            <x-ui.page-header
                title="Registrar llamada"
                subtitle="Guarda el resultado de la llamada y deja trazabilidad inmediata en el historial."
            />
            <form method="POST" action="{{ route('leads.interactions.store', $lead) }}" style="display: grid; gap: 12px;">
                @csrf
                <div class="field">
                    <label for="result">Resultado</label>
                    <select id="result" name="result">
                        <option value="">Selecciona un resultado</option>
                        @foreach ($interactionResults as $result)
                            <option value="{{ $result }}" @selected(old('result') === $result)>{{ $result }}</option>
                        @endforeach
                    </select>
                    @error('result')
                        <div class="meta" style="color: var(--danger);">{{ $message }}</div>
                    @enderror
                </div>
                <div class="field">
                    <label for="interaction_note">Nota</label>
                    <textarea id="interaction_note" name="note" rows="4">{{ old('note') }}</textarea>
                </div>
                <div>
                    <button class="button" type="submit">Registrar llamada</button>
                </div>
            </form>
        </x-ui.card>
    </div>

    <div style="height: 16px;"></div>

    <div style="display: grid; grid-template-columns: 1fr; gap: 16px;">
        <x-ui.card>
            <x-ui.page-header
                title="Recordatorios"
                subtitle="Programa seguimientos operativos, consulta el historico simple y completa pendientes desde el mismo lead."
            />
            @if ($errors->has('reminder'))
                <x-ui.alert type="danger">{{ $errors->first('reminder') }}</x-ui.alert>
            @endif
            <form method="POST" action="{{ route('leads.reminders.store', $lead) }}" style="display: grid; gap: 12px;">
                @csrf
                <div class="grid">
                    <div class="field">
                        <label for="scheduled_at">Fecha y hora</label>
                        <input
                            id="scheduled_at"
                            name="scheduled_at"
                            type="datetime-local"
                            value="{{ old('scheduled_at') }}"
                        >
                        @error('scheduled_at')
                            <div class="meta" style="margin-top: 6px; color: var(--danger);">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="field full">
                        <label for="description">Descripcion del recordatorio</label>
                        <textarea id="description" name="description" rows="3" placeholder="Llamar para confirmar cita, solicitar documentacion, enviar propuesta...">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="meta" style="margin-top: 6px; color: var(--danger);">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="actions">
                    <span class="meta">
                        Puedes crear recordatorios incluso cuando el lead esta sin asignar. Apareceran en bandejas operativas hasta que exista un responsable efectivo.
                    </span>
                    <button class="button" type="submit">Crear recordatorio</button>
                </div>
            </form>

            <div style="height: 18px;"></div>

            <div style="display: grid; gap: 18px;">
                <div>
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 12px; flex-wrap: wrap;">
                        <strong style="font-size: 16px;">Pendientes</strong>
                        <span class="meta">{{ $pendingReminders->count() }} recordatorio(s)</span>
                    </div>
                    <div style="display: grid; gap: 12px;">
                        @forelse ($pendingReminders as $reminder)
                            @php
                                $isOverdue = $reminder->scheduled_at !== null && $reminder->scheduled_at->isPast();
                            @endphp
                            <div
                                class="surface"
                                @if ($isOverdue)
                                    style="padding: 16px 18px; border: 1px solid rgba(180, 35, 24, 0.2); background: rgba(180, 35, 24, 0.04);"
                                @else
                                    style="padding: 16px 18px;"
                                @endif
                            >
                                <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
                                    <div style="display: grid; gap: 8px; flex: 1; min-width: 240px;">
                                        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                                            <x-ui.badge>{{ $isOverdue ? 'Vencido' : 'Pendiente' }}</x-ui.badge>
                                            <span class="meta">{{ $reminder->scheduled_at?->format('Y-m-d H:i') ?: 'Sin fecha' }}</span>
                                        </div>
                                        <div style="font-size: 14px; line-height: 1.65;">{{ $reminder->description }}</div>
                                        <div class="meta">
                                            Creado por: {{ optional($reminder->creator)->name ?: 'Sistema' }}
                                        </div>
                                    </div>
                                    <form method="POST" action="{{ route('leads.reminders.complete', ['reminder' => $reminder]) }}">
                                        @csrf
                                        <button class="button" type="submit">Marcar como completado</button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <x-ui.empty-state
                                title="No hay recordatorios pendientes"
                                description="Crea un recordatorio nuevo para dejar claramente el siguiente paso de seguimiento."
                            />
                        @endforelse
                    </div>
                </div>

                <div>
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 12px; flex-wrap: wrap;">
                        <strong style="font-size: 16px;">Completados</strong>
                        <span class="meta">{{ $completedReminders->count() }} recordatorio(s)</span>
                    </div>
                    <div style="display: grid; gap: 12px;">
                        @forelse ($completedReminders as $reminder)
                            <div class="surface" style="padding: 16px 18px; opacity: 0.84;">
                                <div style="display: grid; gap: 8px;">
                                    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                                        <x-ui.badge>Completado</x-ui.badge>
                                        <span class="meta">
                                            Programado: {{ $reminder->scheduled_at?->format('Y-m-d H:i') ?: 'Sin fecha' }}
                                        </span>
                                        <span class="meta">
                                            Cerrado: {{ $reminder->completed_at?->format('Y-m-d H:i') ?: 'Sin fecha' }}
                                        </span>
                                    </div>
                                    <div style="font-size: 14px; line-height: 1.65;">{{ $reminder->description }}</div>
                                    <div class="meta">
                                        Completado por: {{ optional($reminder->completer)->name ?: 'Sistema' }}
                                    </div>
                                </div>
                            </div>
                        @empty
                            <x-ui.empty-state
                                title="Aun no hay recordatorios completados"
                                description="Cuando cierres seguimientos operativos, apareceran aqui como historial simple del lead."
                            />
                        @endforelse
                    </div>
                </div>
            </div>

        </x-ui.card>

        <x-ui.card>
            <x-ui.page-header
                title="Historial"
                subtitle="Secuencia completa de cambios, llamadas, resultados y acciones asociadas al lead."
            />
            <div style="display: grid; gap: 12px;">
                @php
                    $actionLabels = [
                        'Asignacion actualizada' => 'Asignación actualizada',
                        'Asignacion' => 'Asignación',
                        'Creacion de lead' => 'Creación de lead',
                        'Registro de llamada' => 'Registro de llamada',
                        'Interaccion registrada' => 'Interacción registrada',
                        'Cambio de estado' => 'Cambio de estado',
                        'Recordatorio creado' => 'Recordatorio creado',
                        'Recordatorio completado' => 'Recordatorio completado',
                        'Nota añadida' => 'Nota añadida',
                    ];
                @endphp

                @forelse ($lead->logs as $log)
                    <div class="surface" style="padding: 14px 16px;">
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 8px; flex-wrap: wrap;">
                            <strong>{{ $actionLabels[$log->action] ?? $log->action }}</strong>
                            <span class="meta">{{ $log->created_at?->format('Y-m-d H:i') ?: 'Sin fecha' }}</span>
                        </div>
                        <div class="meta" style="margin-bottom: 4px;">Usuario: {{ optional($log->user)->name ?: 'Sistema' }}</div>
                        @if ($log->result)
                            <div class="meta" style="margin-bottom: 4px;">Resultado: {{ $log->result }}</div>
                        @endif
                        @if ($log->from_status || $log->to_status)
                            <div class="meta" style="margin-bottom: 4px;">
                                Estado: {{ $log->from_status ?: 'N/A' }} -> {{ $log->to_status ?: 'N/A' }}
                            </div>
                        @endif
                        @if ($log->note)
                            <div style="font-size: 14px; line-height: 1.6;">{{ $log->note }}</div>
                        @endif
                    </div>
                @empty
                    <x-ui.empty-state
                        title="Aun no hay historial para este lead"
                        description="Las llamadas, cambios de estado, reasignaciones y recordatorios se iran registrando automaticamente aqui."
                    />
                @endforelse
            </div>
        </x-ui.card>
    </div>
</x-layouts.app>
