<x-layouts.app title="Crear lead">
    <x-ui.card>
        <x-ui.page-header
            title="Alta manual de lead"
            subtitle="Captura rapida con validacion minima. Basta con nombre o telefono para generar un nuevo lead operativo."
        >
            <x-slot:actions>
                <x-ui.button :href="route('leads.index')" variant="link">Volver al listado</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <form method="POST" action="{{ route('leads.store') }}" style="display: grid; gap: 20px;">
            @csrf

            <div style="display: grid; gap: 12px;">
                <span class="badge">Datos principales</span>
                <div class="grid">
                    <div class="field">
                        <label for="name">Nombre</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" placeholder="Nombre del prospecto">
                        @error('name')
                            <div class="meta" style="color: var(--danger);">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="phone">Telefono</label>
                        <input id="phone" name="phone" type="text" value="{{ old('phone') }}" placeholder="Telefono principal">
                        @error('phone')
                            <div class="meta" style="color: var(--danger);">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="email">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="correo@cliente.com">
                        @error('email')
                            <div class="meta" style="color: var(--danger);">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="city">Ciudad</label>
                        <input id="city" name="city" type="text" value="{{ old('city') }}" placeholder="Ciudad o plaza">
                        @error('city')
                            <div class="meta" style="color: var(--danger);">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div style="display: grid; gap: 12px;">
                <span class="badge">Contexto comercial</span>
                <div class="grid">
                    <div class="field">
                        <label for="type">Tipo</label>
                        <input id="type" name="type" type="text" value="{{ old('type') }}" placeholder="Casa, departamento, inversion...">
                        @error('type')
                            <div class="meta" style="color: var(--danger);">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="source">Origen</label>
                        <input id="source" name="source" type="text" value="{{ old('source') }}" placeholder="Facebook, web, referido...">
                        @error('source')
                            <div class="meta" style="color: var(--danger);">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="field full">
                        <label for="status">Estado inicial</label>
                        <select id="status" name="status">
                            @foreach ($statuses as $status)
                                <option value="{{ $status }}" @selected(old('status', 'Nuevo') === $status)>{{ $status }}</option>
                            @endforeach
                        </select>
                        <div class="meta">El estado inicial define el punto de arranque del pipeline para este registro.</div>
                        @error('status')
                            <div class="meta" style="color: var(--danger);">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="surface" style="padding: 16px 18px;">
                <div class="meta">
                    Recomendacion: captura al menos <strong>nombre</strong> o <strong>telefono</strong>. Los demas campos pueden completarse durante el seguimiento.
                </div>
            </div>

            <div class="actions">
                <span class="meta">El lead quedara disponible inmediatamente para seguimiento, asignacion y metricas.</span>
                <button class="button" type="submit">Guardar lead</button>
            </div>
        </form>
    </x-ui.card>
</x-layouts.app>
