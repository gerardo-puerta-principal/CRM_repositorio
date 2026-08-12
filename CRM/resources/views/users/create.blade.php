<x-layouts.app title="Crear usuario">
    <x-ui.card>
        <x-ui.page-header
            title="Crear usuario"
            subtitle="Alta manual de usuarios para operación del CRM. Define rol, supervisor y estado desde el primer momento."
        />

        <form method="POST" action="{{ route('users.store') }}" style="display: grid; gap: 20px;">
            @csrf
            @include('users._form', ['buttonLabel' => 'Guardar usuario'])
        </form>
    </x-ui.card>
</x-layouts.app>
