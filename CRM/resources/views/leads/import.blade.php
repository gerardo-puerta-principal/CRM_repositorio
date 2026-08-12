<x-layouts.app title="Importar leads">
    <x-ui.card>
        <x-ui.page-header
            title="Importar leads"
            subtitle="Sube un archivo CSV o XLSX. El sistema detecta columnas, sugiere mapeo automaticamente y rechaza archivos con mas de {{ $maxRows }} registros."
        >
            <x-slot:actions>
                <x-ui.badge>Limite: {{ $maxRows }} filas</x-ui.badge>
            </x-slot:actions>
        </x-ui.page-header>

        @if (session('status'))
            <x-ui.alert type="success">{{ session('status') }}</x-ui.alert>
        @endif

        @if ($errors->has('file'))
            <x-ui.alert type="danger">{{ $errors->first('file') }}</x-ui.alert>
        @endif

        <div class="surface" style="padding: 20px;">
            <form method="POST" action="{{ route('leads.import.preview') }}" enctype="multipart/form-data" style="display: flex; align-items: end; gap: 12px; flex-wrap: wrap;">
                @csrf
                <div style="min-width: 320px; flex: 1;">
                    <label for="file" style="display: block; margin-bottom: 6px; font-weight: 700;">Archivo</label>
                    <input id="file" name="file" type="file" accept=".csv,.txt,.xlsx" required>
                    <div class="meta" style="margin-top: 8px;">
                        Formatos permitidos: <strong>CSV</strong>, <strong>TXT</strong> y <strong>XLSX</strong>. No se admiten archivos con mas de {{ $maxRows }} filas.
                    </div>
                </div>
                <button class="button" type="submit">Analizar archivo</button>
            </form>
        </div>
    </x-ui.card>

    @if (is_array($preview))
        <div style="height: 16px;"></div>

        <x-ui.card>
            <x-ui.page-header
                title="Previsualizacion"
                subtitle="Revisa las columnas detectadas, confirma el mapeo y valida una muestra antes de importar."
            />

            <div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; margin-bottom: 20px;">
                <div class="surface" style="padding: 16px 18px;">
                    <strong>Archivo</strong>
                    <div class="meta" style="margin-top: 8px;">{{ $preview['original_name'] }}</div>
                </div>
                <div class="surface" style="padding: 16px 18px;">
                    <strong>Registros detectados</strong>
                    <div class="meta" style="margin-top: 8px;">{{ $preview['total_rows'] }}</div>
                </div>
                <div class="surface" style="padding: 16px 18px;">
                    <strong>Columnas detectadas</strong>
                    <div class="meta" style="margin-top: 8px;">{{ count($preview['headers']) }}</div>
                </div>
            </div>

            <form method="POST" action="{{ route('leads.import.store') }}" style="display: grid; gap: 20px;">
                @csrf

                <div style="display: grid; gap: 12px;">
                    <span class="badge">Mapeo de columnas</span>
                    <div class="grid">
                        @foreach ($targetFields as $field => $label)
                            <div class="field">
                                <label for="mapping_{{ $field }}">{{ $label }}</label>
                                <select id="mapping_{{ $field }}" name="mapping[{{ $field }}]">
                                    <option value="">Ignorar</option>
                                    @foreach ($preview['headers'] as $header)
                                        <option
                                            value="{{ $header }}"
                                            @selected(old('mapping.'.$field, $preview['suggested_mapping'][$field] ?? '') === $header)
                                        >
                                            {{ $header }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div style="display: grid; gap: 12px;">
                    <span class="badge">Muestra detectada</span>
                    <div style="overflow-x: auto; border: 1px solid var(--border); border-radius: 20px; background: var(--panel);">
                        <table>
                            <thead>
                                <tr style="text-align: left; border-bottom: 1px solid var(--border); background: rgba(37, 99, 235, 0.04);">
                                    @foreach ($preview['headers'] as $header)
                                        <th style="padding: 14px 16px;">{{ $header }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($preview['sample_rows'] as $sampleRow)
                                    <tr style="border-bottom: 1px solid var(--border);">
                                        @foreach ($preview['headers'] as $header)
                                            <td style="padding: 14px 16px;">{{ $sampleRow[$header] ?? '' }}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="actions">
                    <div class="meta">
                        Se importaran solo filas con nombre o telefono. Las columnas sin mapear se ignoran y el archivo original quedara ligado al registro de importacion.
                    </div>
                    <button class="button" type="submit">Confirmar importacion</button>
                </div>
            </form>
        </x-ui.card>
    @endif
</x-layouts.app>
