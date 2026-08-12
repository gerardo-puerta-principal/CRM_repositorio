<x-layouts.app title="Metricas">
    <x-ui.card>
        <x-ui.page-header
            title="Metricas operativas"
            subtitle="Control real del equipo por agente, con actividad del periodo, avance actual del pipeline y recordatorios operativos vencidos."
        >
            <x-slot:actions>
                <form method="GET" action="{{ route('metrics.index') }}" style="display: flex; align-items: end; gap: 12px; flex-wrap: wrap;">
                    <div class="field" style="min-width: 190px;">
                        <label for="period">Rango</label>
                        <select id="period" name="period">
                            @foreach ($periodOptions as $option)
                                <option value="{{ $option['key'] }}" @selected($selectedPeriod === $option['key'])>{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button class="button" type="submit">Aplicar</button>
                </form>
            </x-slot:actions>
        </x-ui.page-header>

        <div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px;">
            <div class="surface" style="padding: 18px;">
                <div class="meta" style="margin-bottom: 8px;">Leads visibles</div>
                <strong style="font-size: 30px; letter-spacing: -0.04em;">{{ $summary['total_leads'] }}</strong>
            </div>
            <div class="surface" style="padding: 18px;">
                <div class="meta" style="margin-bottom: 8px;">Sin asignar</div>
                <strong style="font-size: 30px; letter-spacing: -0.04em;">{{ $summary['unassigned_leads'] }}</strong>
            </div>
            <div class="surface" style="padding: 18px;">
                <div class="meta" style="margin-bottom: 8px;">Recordatorios pendientes vencidos</div>
                <strong style="font-size: 30px; letter-spacing: -0.04em;">{{ $summary['overdue_pending_reminders'] }}</strong>
            </div>
        </div>
    </x-ui.card>

    <div style="height: 16px;"></div>

    <x-ui.card>
        <x-ui.page-header
            title="Resumen actual del pipeline visible"
            subtitle="Vista rapida del inventario visible para el rol autenticado y el periodo seleccionado."
        />
        <div style="display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px;">
            @foreach ($statusRows as $row)
                <div class="surface" style="padding: 16px 18px;">
                    <div class="meta" style="margin-bottom: 8px;">{{ $row['status'] }}</div>
                    <strong style="font-size: 24px; letter-spacing: -0.04em;">{{ $row['total'] }}</strong>
                </div>
            @endforeach
        </div>
    </x-ui.card>

    <div style="height: 16px;"></div>

    <x-ui.card>
        <x-ui.page-header
            title="Tabla operativa por agente"
            subtitle="Actividad por agente con llamadas, trabajo del periodo, recordatorios pendientes vencidos, resultados y estado actual del pipeline."
        />
        <div style="overflow-x: auto; border: 1px solid var(--border); border-radius: 20px; background: var(--panel);">
            <table style="min-width: 1500px;">
                <thead>
                    <tr style="text-align: left; border-bottom: 1px solid var(--border); background: rgba(37, 99, 235, 0.04);">
                        <th style="padding: 14px 16px; vertical-align: bottom;">Agente</th>
                        <th style="padding: 14px 16px; vertical-align: bottom;">Llamadas</th>
                        <th style="padding: 14px 16px; vertical-align: bottom;">Leads trabajados</th>
                        <th style="padding: 14px 16px; vertical-align: bottom;">Sin seguimiento</th>
                        <th style="padding: 14px 16px; vertical-align: bottom;">Recordatorios vencidos</th>
                        <th style="padding: 14px 16px; vertical-align: bottom;">Resultados de llamadas</th>
                        <th style="padding: 14px 16px; vertical-align: bottom;">Pipeline actual</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($metricRows as $row)
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td style="padding: 16px;">
                                <strong>{{ $row['agent_name'] }}</strong>
                            </td>
                            <td style="padding: 16px;">{{ $row['calls_made'] }}</td>
                            <td style="padding: 16px;">{{ $row['leads_worked'] }}</td>
                            <td style="padding: 16px;">{{ $row['stale_leads'] }}</td>
                            <td style="padding: 16px;">{{ $row['overdue_reminders'] }}</td>
                            <td style="padding: 16px;">
                                <div style="display: grid; gap: 8px;">
                                    @foreach ($resultColumns as $result)
                                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; min-width: 220px;">
                                            <span class="meta">{{ $result }}</span>
                                            <strong style="font-size: 13px;">{{ $row['call_results'][$result] ?? 0 }}</strong>
                                        </div>
                                    @endforeach
                                </div>
                            </td>
                            <td style="padding: 16px;">
                                <div style="display: grid; grid-template-columns: repeat(2, minmax(120px, 1fr)); gap: 8px 12px; min-width: 320px;">
                                    @foreach ($statusColumns as $status)
                                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                                            <span class="meta">{{ $status }}</span>
                                            <strong style="font-size: 13px;">{{ $row['pipeline_statuses'][$status] ?? 0 }}</strong>
                                        </div>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="padding: 0;">
                                <x-ui.empty-state
                                    title="No hay agentes activos para mostrar en el periodo actual"
                                    description="Crea agentes, actívalos o ajusta el rango para comenzar a ver actividad operativa."
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
</x-layouts.app>
