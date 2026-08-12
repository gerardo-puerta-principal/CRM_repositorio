<?php

return [
    'installed_lock' => storage_path('app/installed.lock'),
    'installer_key' => env('INSTALLER_KEY', ''),
    'import_max_rows' => 5000,
    'import_target_fields' => [
        'name' => 'Nombre',
        'phone' => 'Telefono',
        'email' => 'Email',
        'city' => 'Ciudad',
        'type' => 'Tipo',
        'source' => 'Origen',
    ],
    'import_header_aliases' => [
        'name' => ['name', 'nombre', 'nombre completo', 'lead', 'cliente', 'prospecto', 'contacto'],
        'phone' => ['phone', 'telefono', 'tel', 'cel', 'celular', 'movil', 'mobile', 'whatsapp', 'numero'],
        'email' => ['email', 'correo', 'correo electronico', 'mail', 'e-mail'],
        'city' => ['city', 'ciudad', 'municipio', 'ubicacion', 'localidad'],
        'type' => ['type', 'tipo', 'producto', 'servicio', 'categoria'],
        'source' => ['source', 'origen', 'fuente', 'canal', 'campaign', 'campana', 'medio'],
    ],
    'lead_statuses' => [
        'Nuevo',
        'Por llamar',
        'No contesta',
        'Contactado',
        'Interesado',
        'Cita agendada',
        'Cerrado',
        'Perdido',
    ],
    'interaction_results' => [
        'Llamada realizada',
        'No contesta',
        'Buzon',
        'Whatsapp enviado',
        'Correo enviado',
        'Interesado',
        'No interesado',
        'Seguimiento',
    ],
];
