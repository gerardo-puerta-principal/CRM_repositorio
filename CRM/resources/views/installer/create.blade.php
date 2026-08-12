<x-layouts.guest title="Instalacion CRM Puerta Principal">
    <x-auth.login-panel
        title="Instalacion inicial"
        eyebrow="Primer arranque"
        subtitle="Configura la conexion MySQL y crea la cuenta principal de administracion. Este paso solo debe ejecutarse una vez y dejara el sistema listo para operar."
    >
        @if ($errors->has('installer'))
            <x-ui.alert type="danger">{{ $errors->first('installer') }}</x-ui.alert>
        @endif

        @if ($errors->any() && ! $errors->has('installer'))
            <x-ui.alert type="danger">Revisa los campos marcados y vuelve a intentarlo.</x-ui.alert>
        @endif

        <div style="display: grid; gap: 18px;">
            <div style="display: grid; gap: 10px; padding: 16px 18px; border-radius: 18px; background: rgba(37, 99, 235, 0.06); border: 1px solid rgba(37, 99, 235, 0.1);">
                <strong style="font-size: 14px; color: var(--text);">Que hara esta instalacion</strong>
                <div class="meta">
                    Escribira el archivo <strong>.env</strong>, probara la conexion con MySQL, ejecutara migraciones y creara la cuenta principal de Super Admin.
                </div>
            </div>

            <form method="POST" action="{{ route('install.store') }}" style="display: grid; gap: 20px;">
                @csrf

                <div style="display: grid; gap: 12px;">
                    <span class="badge">Conexion MySQL</span>
                    <div class="grid">
                        <div class="field">
                            <label for="db_host">Host de base de datos</label>
                            <input id="db_host" name="db_host" type="text" value="{{ old('db_host', '127.0.0.1') }}" required placeholder="localhost">
                            <div class="meta">En hosting compartido suele funcionar mejor con <strong>localhost</strong>.</div>
                        </div>

                        <div class="field">
                            <label for="db_port">Puerto</label>
                            <input id="db_port" name="db_port" type="number" value="{{ old('db_port', 3306) }}" required>
                            <div class="meta">El puerto estandar de MySQL es 3306.</div>
                        </div>

                        <div class="field">
                            <label for="db_database">Base de datos</label>
                            <input id="db_database" name="db_database" type="text" value="{{ old('db_database') }}" required placeholder="nombre_base">
                        </div>

                        <div class="field">
                            <label for="db_username">Usuario MySQL</label>
                            <input id="db_username" name="db_username" type="text" value="{{ old('db_username') }}" required placeholder="usuario_mysql">
                        </div>

                        <div class="field full">
                            <label for="db_password">Contrasena MySQL</label>
                            <input id="db_password" name="db_password" type="password" autocomplete="new-password" placeholder="Contrasena del usuario MySQL">
                        </div>
                    </div>
                </div>

                <div style="display: grid; gap: 12px;">
                    <span class="badge">Super Admin</span>
                    <div class="grid">
                        <div class="field">
                            <label for="admin_name">Nombre del Super Admin</label>
                            <input id="admin_name" name="admin_name" type="text" value="{{ old('admin_name') }}" required placeholder="Nombre completo">
                        </div>

                        <div class="field">
                            <label for="admin_email">Email del Super Admin</label>
                            <input id="admin_email" name="admin_email" type="email" value="{{ old('admin_email') }}" required placeholder="admin@empresa.com">
                        </div>

                        <div class="field">
                            <label for="admin_password">Contrasena del Super Admin</label>
                            <input id="admin_password" name="admin_password" type="password" required autocomplete="new-password" placeholder="Crea una contrasena segura">
                        </div>

                        <div class="field">
                            <label for="admin_password_confirmation">Confirmar contrasena</label>
                            <input id="admin_password_confirmation" name="admin_password_confirmation" type="password" required autocomplete="new-password" placeholder="Repite la contrasena">
                        </div>
                    </div>
                </div>

                <div class="actions">
                    <span class="meta">Cuando termine, el instalador quedara cerrado y el acceso normal pasara al login del CRM.</span>
                    <button class="button" type="submit">Instalar CRM</button>
                </div>
            </form>
        </div>
    </x-auth.login-panel>
</x-layouts.guest>
