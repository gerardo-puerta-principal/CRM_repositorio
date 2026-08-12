@php
    $editing = isset($userModel);
    $selectedRole = old('role', $editing ? $userModel->role : \App\Models\User::ROLE_AGENT);
    $selectedSupervisor = (int) old('supervisor_id', $editing ? ($userModel->supervisor_id ?? 0) : 0);
@endphp

<div style="display: grid; gap: 20px;">
    <div style="display: grid; gap: 12px;">
        <span class="badge">Datos de acceso</span>
        <div class="grid">
            <div class="field">
                <label for="name">Nombre</label>
                <input id="name" name="name" type="text" value="{{ old('name', $editing ? $userModel->name : '') }}" placeholder="Nombre completo">
                @error('name')
                    <div class="meta" style="color: var(--danger);">{{ $message }}</div>
                @enderror
            </div>

            <div class="field">
                <label for="email">Correo electrónico</label>
                <input id="email" name="email" type="email" value="{{ old('email', $editing ? $userModel->email : '') }}" placeholder="correo@empresa.com">
                @error('email')
                    <div class="meta" style="color: var(--danger);">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    <div style="display: grid; gap: 12px;">
        <span class="badge">Rol y estructura</span>
        <div class="grid">
            <div class="field">
                <label for="role">Rol</label>
                <select id="role" name="role">
                    @foreach ($roles as $roleValue => $roleLabel)
                        <option value="{{ $roleValue }}" @selected($selectedRole === $roleValue)>{{ $roleLabel }}</option>
                    @endforeach
                </select>
                @error('role')
                    <div class="meta" style="color: var(--danger);">{{ $message }}</div>
                @enderror
            </div>

            <div class="field">
                <label for="supervisor_id">Supervisor</label>
                <select id="supervisor_id" name="supervisor_id">
                    <option value="">Sin supervisor</option>
                    @foreach ($supervisors as $supervisor)
                        <option value="{{ $supervisor->id }}" @selected($selectedSupervisor === $supervisor->id)>{{ $supervisor->name }}</option>
                    @endforeach
                </select>
                <div class="meta">Obligatorio para agentes. Supervisores y superadmin pueden quedar sin supervisor.</div>
                @error('supervisor_id')
                    <div class="meta" style="color: var(--danger);">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    @if (! $editing)
        <div style="display: grid; gap: 12px;">
            <span class="badge">Contraseña inicial</span>
            <div class="grid">
                <div class="field">
                    <label for="password">Contraseña</label>
                    <input id="password" name="password" type="password" placeholder="Contraseña segura">
                    @error('password')
                        <div class="meta" style="color: var(--danger);">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="password_confirmation">Confirmar contraseña</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" placeholder="Repite la contraseña">
                </div>
            </div>
        </div>
    @endif

    <div class="surface" style="padding: 16px 18px;">
        <label style="display: inline-flex; align-items: center; gap: 10px; font-weight: 600;">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $editing ? $userModel->is_active : true)) style="width: auto; margin: 0;">
            <span>Usuario activo</span>
        </label>
        <div class="meta" style="margin-top: 8px;">Los usuarios inactivos no podrán iniciar sesión ni operar leads.</div>
    </div>
</div>

<div class="actions">
    <span class="meta">Verifica rol, supervisor y estado antes de guardar para evitar inconsistencias en visibilidad y operación.</span>
    <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
        <a href="{{ route('users.index') }}" style="display: inline-flex; align-items: center; color: var(--muted); text-decoration: none;">Volver a usuarios</a>
        <button class="button" type="submit">{{ $buttonLabel }}</button>
    </div>
</div>
