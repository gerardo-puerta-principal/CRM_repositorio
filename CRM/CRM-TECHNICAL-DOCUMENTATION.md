# CRM Puerta Principal - Documentacion Tecnica Completa

## 1. Resumen General

- Nombre del proyecto: `CRM Puerta Principal`
- Tipo de sistema: CRM web monolitico, server-rendered, orientado a operacion comercial.
- Objetivo de negocio: centralizar la captura, seguimiento, asignacion y medicion de leads para un equipo comercial con roles diferenciados.
- Objetivo tecnico: funcionar en hosting compartido `cPanel`, sin depender de colas, workers permanentes, websockets ni frontend SPA.

### Tipo de usuarios

- `super_admin`
  - administra usuarios
  - puede ver todos los leads
  - puede eliminar leads logicamente
  - puede impersonar usuarios
  - puede ver metricas globales
- `supervisor`
  - puede ver leads de su equipo bajo reglas de visibilidad
  - puede asignar leads a agentes de su equipo
  - puede ejecutar round robin sobre leads visibles
  - puede ver metricas de su equipo
- `agent`
  - puede iniciar sesion
  - puede crear leads
  - puede ver unicamente los leads asignados a el
  - puede registrar llamadas, actualizar estado y programar recordatorios sobre leads visibles

### Procesos que resuelve

- instalacion inicial por web
- autenticacion con sesiones Laravel
- alta manual de leads
- importacion de leads desde `CSV`, `TXT` y `XLSX`
- clasificacion por pipeline comercial
- seguimiento con historial de cambios
- registro de llamadas
- programacion de recordatorios
- asignacion manual de leads
- asignacion asistida por round robin
- metricas operativas por agente y por estado
- administracion de usuarios
- impersonacion de usuarios

### Estado actual del desarrollo

- El proyecto esta funcional como MVP.
- El nucleo operativo esta implementado: instalador, login, leads, historial, importacion, asignacion, usuarios y metricas.
- El sistema es principalmente HTML server-rendered con Blade.
- No existe API REST publica ni frontend SPA.
- No existe un sistema activo de notificaciones para recordatorios; solo se guarda `reminder_at` y se refleja en metricas.
- El borrado de leads es logico mediante `soft delete`.
- Hay problemas operativos observados durante la sesion:
  - error `500` pendiente al analizar ciertos archivos de importacion
  - comportamiento a validar en cambio de sesion/impersonacion
  - inconsistencia reportada por usuario al actualizar y filtrar por estado

## 2. Stack Tecnologico

### Backend

- Lenguaje: `PHP 8.3+`
- Framework: `Laravel 13.8`
- Patron principal: MVC server-rendered con servicios de aplicacion
- Consola: `artisan`

### Frontend

- Motor de vistas: `Blade`
- Rendering: SSR tradicional
- CSS:
  - estilos inline extensos dentro de `resources/views/partials/shell-app.blade.php`
  - estilos inline extensos dentro de `resources/views/partials/shell-guest.blade.php`
  - `Tailwind CSS v4` configurado, pero el sistema actual depende sobre todo de CSS propio en vistas Blade
- JS:
  - no hay logica frontend significativa
  - `resources/js/app.js` esta practicamente vacio
- Build tool: `Vite 8`

### Base de datos

- Motor objetivo: `MySQL`
- Conexion validada por el instalador via `PDO`
- Driver principal esperado: `mysql`

### ORM

- `Eloquent ORM`

### Librerias principales

- `laravel/framework`
- `openspout/openspout`
  - lectura de archivos `XLSX`
- `laravel/tinker`
- Dev:
  - `phpunit/phpunit`
  - `laravel/pint`
  - `fakerphp/faker`
  - `nunomaduro/collision`
  - `mockery/mockery`

### Servicios externos

- No hay integraciones externas activas en el flujo operativo actual.
- Existen configuraciones Laravel opcionales para:
  - correo SMTP / SES / Postmark / Resend
  - `AWS S3`
  - notificaciones Slack
- En el estado actual:
  - mailer por defecto: `log`
  - filesystem activo esperado: `local`
  - queue activa esperada: `sync`

### Sistema de autenticacion

- Guard: `web`
- Driver: `session`
- Provider: `eloquent` sobre `App\Models\User`
- `remember me`: habilitado
- control de acceso adicional:
  - rate limiting por correo e IP
  - middleware `active`
  - middleware `role`

### Sistema de almacenamiento de archivos

- Disk por defecto: `local`
- Archivos temporales de importacion:
  - `storage/app/imports/tmp`
- Lock de instalacion:
  - `storage/app/installed.lock`
- Posible soporte `public` y `s3`, no usado por la logica principal

### Herramientas de despliegue

- `cPanel`
- `Composer`
- instalador web propio
- posibilidad de subir `vendor/` precompilado como fallback
- flujos GitHub Actions para tests y automatizaciones de repositorio

## 3. Arquitectura del Proyecto

### Arquitectura general

El sistema es un monolito Laravel clasico con MVC y renderizado del lado del servidor.

Flujo tipico:

1. El navegador hace una peticion HTTP.
2. Laravel resuelve la ruta en `routes/web.php`.
3. Se ejecutan middlewares de autenticacion, instalacion y roles.
4. El controlador procesa la peticion.
5. El controlador usa modelos Eloquent y, en algunos casos, servicios de aplicacion.
6. Se devuelve una vista Blade o un redirect con `flash session`.

### Monolito o microservicios

- Monolito.
- No existen microservicios, workers dedicados ni modulos desplegables por separado.

### Flujo de datos

- Entrada:
  - formularios HTML
  - archivos de importacion
  - parametros query para filtros y periodos
- Procesamiento:
  - validacion en controlador o `FormRequest`
  - reglas de negocio en controladores y modelos
  - servicios para instalacion e importacion
- Persistencia:
  - MySQL mediante Eloquent y Query Builder
- Salida:
  - HTML Blade
  - redirects con mensajes de estado

### Capas principales

- Capa de presentacion
  - vistas Blade
  - componentes UI Blade
  - layouts `app` y `guest`
- Capa HTTP
  - rutas web
  - middlewares
  - controladores
  - requests de validacion
- Capa de dominio ligera
  - modelos `User`, `Lead`, `LeadLog`
  - reglas de visibilidad y roles
- Capa de servicios
  - `InstallerService`
  - `LeadImportService`
- Capa de datos
  - migraciones
  - Eloquent
  - Query Builder

### Patron de diseno utilizado

- MVC clasico de Laravel
- Service Layer para casos de uso complejos:
  - instalacion
  - importacion
- Config-driven enums:
  - estados de lead
  - resultados de interaccion
- Middleware-driven access control:
  - instalacion
  - usuario activo
  - roles

### Interaccion entre modulos

- `Installer`
  - escribe `.env`
  - ejecuta migraciones
  - crea o actualiza el `super_admin`
  - marca instalacion terminada
- `Auth`
  - permite acceso solo a usuarios activos
  - limpia datos de impersonacion al login/logout
- `Users`
  - define estructura organizacional (`supervisor_id`)
  - impacta permisos de visibilidad y asignacion
- `Leads`
  - es el nucleo del sistema
  - consume configuraciones de estados
  - genera historial en `lead_logs`
- `Lead Import`
  - crea leads masivos
  - genera log `Importado`
- `Metrics`
  - consulta `leads` y `lead_logs`
  - respeta visibilidad por rol

## 4. Estructura de Carpetas

### Arbol del proyecto

```text
CRM/
├── .github/
│   ├── dependabot.yml
│   └── workflows/
│       ├── dependabot-auto-merge.yml
│       ├── issues.yml
│       ├── pull-requests.yml
│       ├── tests.yml
│       └── update-changelog.yml
├── Logo/
│   ├── Artboard 1.svg
│   └── Artboard 8.svg
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/
│   │   │   │   └── AuthController.php
│   │   │   ├── Installer/
│   │   │   │   └── InstallerController.php
│   │   │   ├── Controller.php
│   │   │   ├── LeadController.php
│   │   │   ├── LeadImportController.php
│   │   │   ├── MetricsController.php
│   │   │   └── UserController.php
│   │   ├── Middleware/
│   │   │   ├── EnsureUserIsActive.php
│   │   │   ├── ProtectInstallerAccess.php
│   │   │   ├── RedirectIfInstalled.php
│   │   │   ├── RedirectToInstaller.php
│   │   │   └── RequireRole.php
│   │   └── Requests/
│   │       ├── InstallApplicationRequest.php
│   │       ├── LeadImportConfirmRequest.php
│   │       └── LeadImportPreviewRequest.php
│   ├── Models/
│   │   ├── Lead.php
│   │   ├── LeadLog.php
│   │   └── User.php
│   ├── Providers/
│   │   └── AppServiceProvider.php
│   └── Services/
│       ├── Installer/
│       │   ├── InstallationStatus.php
│       │   └── InstallerService.php
│       └── LeadImport/
│           └── LeadImportService.php
├── bootstrap/
│   ├── app.php
│   ├── providers.php
│   └── cache/
├── config/
│   ├── app.php
│   ├── auth.php
│   ├── cache.php
│   ├── crm.php
│   ├── database.php
│   ├── filesystems.php
│   ├── logging.php
│   ├── mail.php
│   ├── queue.php
│   ├── services.php
│   └── session.php
├── database/
│   ├── factories/
│   │   └── UserFactory.php
│   ├── migrations/
│   │   ├── 0001_01_01_000000_create_users_table.php
│   │   ├── 0001_01_01_000001_create_cache_table.php
│   │   ├── 0001_01_01_000002_create_jobs_table.php
│   │   ├── 2026_06_16_000001_create_leads_table.php
│   │   ├── 2026_06_16_000002_create_lead_logs_table.php
│   │   ├── 2026_06_16_000003_add_metrics_indexes.php
│   │   └── 2026_06_17_000004_add_deleted_at_to_leads_table.php
│   └── seeders/
│       └── DatabaseSeeder.php
├── inspiracion/
│   ├── app-menu-with-lock-screen/
│   └── app-menu-with-lock-screen.zip
├── public/
│   ├── .htaccess
│   ├── favicon.ico
│   ├── index.php
│   └── robots.txt
├── resources/
│   ├── css/
│   │   └── app.css
│   ├── js/
│   │   └── app.js
│   └── views/
│       ├── auth/
│       │   └── login.blade.php
│       ├── components/
│       │   ├── auth/
│       │   │   └── login-panel.blade.php
│       │   ├── layouts/
│       │   │   ├── app.blade.php
│       │   │   └── guest.blade.php
│       │   └── ui/
│       │       ├── alert.blade.php
│       │       ├── badge.blade.php
│       │       ├── button.blade.php
│       │       ├── card.blade.php
│       │       ├── empty-state.blade.php
│       │       └── page-header.blade.php
│       ├── installer/
│       │   └── create.blade.php
│       ├── layouts/
│       │   ├── app.blade.php
│       │   └── guest.blade.php
│       ├── leads/
│       │   ├── create.blade.php
│       │   ├── import.blade.php
│       │   ├── index.blade.php
│       │   └── show.blade.php
│       ├── metrics/
│       │   └── index.blade.php
│       ├── partials/
│       │   ├── shell-app.blade.php
│       │   └── shell-guest.blade.php
│       ├── users/
│       │   ├── _form.blade.php
│       │   ├── create.blade.php
│       │   ├── edit.blade.php
│       │   └── index.blade.php
│       ├── welcome.blade.php
│       └── views.zip
├── routes/
│   ├── console.php
│   └── web.php
├── storage/
│   ├── app/
│   │   ├── private/
│   │   └── public/
│   ├── framework/
│   │   ├── cache/
│   │   ├── sessions/
│   │   ├── testing/
│   │   └── views/
│   └── logs/
├── tests/
│   ├── Feature/
│   │   └── ExampleTest.php
│   ├── Unit/
│   │   └── ExampleTest.php
│   └── TestCase.php
├── .editorconfig
├── .env.example
├── .gitattributes
├── .gitignore
├── .npmrc
├── .styleci.yml
├── CHANGELOG.md
├── DEPLOY-CPANEL.md
├── README.md
├── artisan
├── composer.json
├── composer.lock
├── debug-import-upload-500.md
├── debug-missing-app-key.md
├── package.json
├── phpunit.xml
├── vendor.zip
└── vite.config.js
```

### Funcion de cada carpeta

- `.github/`: automatizaciones del repositorio.
- `Logo/`: recursos graficos de marca usados como referencia visual.
- `app/`: logica principal del backend.
- `bootstrap/`: arranque de Laravel y cache de framework.
- `config/`: configuracion del framework y del CRM.
- `database/`: migraciones, factories y seeders.
- `inspiracion/`: material visual externo usado como referencia de diseno.
- `public/`: punto de entrada web.
- `resources/`: vistas Blade, CSS y JS fuente.
- `routes/`: definicion de rutas web y consola.
- `storage/`: archivos temporales, logs, sesiones y lock de instalacion.
- `tests/`: pruebas basicas de ejemplo.

## 5. Base de Datos

### Vision general del esquema

Tablas funcionales del CRM:

- `users`
- `leads`
- `lead_logs`

Tablas de framework Laravel:

- `password_reset_tokens`
- `sessions`
- `cache`
- `cache_locks`
- `jobs`
- `job_batches`
- `failed_jobs`

### Tabla: `users`

- Proposito: almacenar cuentas del CRM y la estructura jerarquica del equipo.
- Llave primaria: `id`
- Llave foranea:
  - `supervisor_id -> users.id`
- Indices:
  - `email` unico
  - indice en `role`
  - indice implicito en `supervisor_id`

Campos:

| Campo | Tipo | Nulo | Descripcion |
|---|---|---:|---|
| `id` | bigint autoincrement | no | PK |
| `name` | string | no | nombre del usuario |
| `email` | string unique | no | correo de acceso |
| `email_verified_at` | timestamp | si | campo scaffold Laravel, no usado por logica del CRM |
| `password` | string | no | password hasheado |
| `role` | string(30) | no | `super_admin`, `supervisor`, `agent` |
| `supervisor_id` | foreignId | si | supervisor del agente |
| `is_active` | boolean | no | habilita o bloquea acceso |
| `last_login_at` | timestamp | si | ultimo login exitoso |
| `remember_token` | string | si | remember me |
| `created_at` | timestamp | no | auditoria basica |
| `updated_at` | timestamp | no | auditoria basica |

### Tabla: `password_reset_tokens`

- Proposito: tabla scaffold de Laravel para recuperacion de contrasena.
- En la practica: la recuperacion automatica no esta expuesta en UI.

Campos:

| Campo | Tipo | Nulo | Descripcion |
|---|---|---:|---|
| `email` | string | no | PK |
| `token` | string | no | token de reset |
| `created_at` | timestamp | si | fecha de creacion |

### Tabla: `sessions`

- Proposito: persistencia de sesiones si se usa driver `database`.
- Nota: el instalador fuerza `SESSION_DRIVER=file` para produccion compartida, por lo que esta tabla puede existir pero no estar activa.

Campos:

| Campo | Tipo | Nulo | Descripcion |
|---|---|---:|---|
| `id` | string | no | PK |
| `user_id` | foreignId | si | usuario asociado |
| `ip_address` | string(45) | si | IP |
| `user_agent` | text | si | agente de navegador |
| `payload` | longText | no | contenido serializado |
| `last_activity` | integer | no | timestamp de actividad |

### Tabla: `leads`

- Proposito: entidad principal comercial del sistema.
- Llave primaria: `id`
- Llaves foraneas:
  - `assigned_user_id -> users.id`
  - `created_by -> users.id`
- Soft delete: `deleted_at`
- Indices:
  - `phone`
  - `email`
  - `status`
  - `reminder_at`
  - `last_contact_at`
  - `deleted_at`
  - compuesto `assigned_user_id + status`
  - compuesto `assigned_user_id + last_contact_at`
  - compuesto `assigned_user_id + reminder_at`

Campos:

| Campo | Tipo | Nulo | Descripcion |
|---|---|---:|---|
| `id` | bigint autoincrement | no | PK |
| `name` | string | si | nombre del lead |
| `phone` | string(50) | si | telefono principal |
| `email` | string | si | correo del lead |
| `city` | string | si | ciudad/plaza |
| `type` | string | si | tipo de interes |
| `source` | string | si | origen/canal |
| `status` | string(30) | no | estado del pipeline |
| `assigned_user_id` | foreignId | si | agente responsable |
| `created_by` | foreignId | si | usuario que creo el lead |
| `reminder_at` | timestamp | si | proximo recordatorio |
| `last_contact_at` | timestamp | si | ultimo contacto |
| `import_file_name` | string | si | archivo de origen si fue importado |
| `imported_at` | timestamp | si | fecha de importacion |
| `created_at` | timestamp | no | fecha de creacion |
| `updated_at` | timestamp | no | fecha de actualizacion |
| `deleted_at` | timestamp | si | soft delete |

### Tabla: `lead_logs`

- Proposito: bitacora de actividad del lead.
- Llave primaria: `id`
- Llaves foraneas:
  - `lead_id -> leads.id`
  - `user_id -> users.id`
- Indices:
  - `action`
  - `created_at`
  - compuesto `action + user_id + created_at`
  - compuesto `action + result + created_at`

Campos:

| Campo | Tipo | Nulo | Descripcion |
|---|---|---:|---|
| `id` | bigint autoincrement | no | PK |
| `lead_id` | foreignId | no | lead afectado |
| `user_id` | foreignId | si | usuario que ejecuto la accion |
| `action` | string(50) | no | tipo de accion |
| `result` | string | si | resultado de llamada/interaccion |
| `note` | text | si | observacion |
| `from_status` | string(30) | si | estado anterior |
| `to_status` | string(30) | si | estado resultante |
| `meta_json` | json | si | metadata libre |
| `created_at` | timestamp | no | momento del evento |

### Tabla: `cache`

- Proposito: cache de Laravel si se usa driver `database`.
- Estado actual esperado: no activa en produccion normal, porque el instalador fuerza `CACHE_STORE=file`.

### Tabla: `cache_locks`

- Proposito: locks del cache driver `database`.

### Tabla: `jobs`

- Proposito: trabajos en cola si se usa queue database.
- Estado actual esperado: no activa porque el instalador fuerza `QUEUE_CONNECTION=sync`.

### Tabla: `job_batches`

- Proposito: lotes de trabajos Laravel.

### Tabla: `failed_jobs`

- Proposito: registro de jobs fallidos.

### Diagrama textual de relaciones

```text
users
  ├─< users (supervisor_id)
  ├─< leads.created_by
  ├─< leads.assigned_user_id
  └─< lead_logs.user_id

leads
  └─< lead_logs.lead_id
```

### Observaciones importantes del esquema

- No existen tablas de `clientes`, `propiedades`, `tareas`, `cotizaciones`, `negocios`, `oportunidades` ni `pipeline_stages`.
- El pipeline vive en el campo `leads.status`.
- El historial vive en `lead_logs`, no en eventos versionados ni auditoria formal.
- Los recordatorios no tienen tabla propia; viven en `leads.reminder_at`.

## 6. Modelos de Negocio

### 6.1 Usuario (`User`)

Responsabilidades:

- autenticar acceso
- representar rol y estado
- definir jerarquia supervisor/agente
- servir como creador o asignado de leads
- servir como actor en el historial

Relaciones:

- `supervisor()`: pertenece a otro `User`
- `agents()`: tiene muchos `User`
- `assignedLeads()`: tiene muchos `Lead`
- `createdLeads()`: tiene muchos `Lead`
- `leadLogs()`: tiene muchos `LeadLog`

Reglas de negocio:

- solo tres roles validos
- los agentes deben pertenecer a un supervisor valido al crearlos/editaros
- un usuario inactivo no puede iniciar sesion
- un super admin no puede desactivarse a si mismo ni quitarse su rol desde UI

### 6.2 Lead (`Lead`)

Responsabilidades:

- concentrar informacion minima del prospecto
- representar el estado comercial actual
- asociar responsable y creador
- guardar recordatorio y ultimo contacto
- ser visible segun reglas por rol
- soportar soft delete

Relaciones:

- `assignedUser()`: pertenece a `User`
- `creator()`: pertenece a `User`
- `logs()`: tiene muchos `LeadLog`

Reglas de negocio:

- un lead puede crearse con nombre o telefono; no se exigen ambos
- los estados validos provienen de `config/crm.php`
- los agentes solo ven leads asignados a ellos
- los supervisores ven:
  - leads asignados a agentes activos de su equipo
  - leads sin asignar creados por ellos o por su equipo
- los `super_admin` ven todo
- el borrado es logico

Estados de negocio actuales:

- `Nuevo`
- `Por llamar`
- `No contesta`
- `Contactado`
- `Interesado`
- `Cita agendada`
- `Cerrado`
- `Perdido`

### 6.3 Historial de lead (`LeadLog`)

Responsabilidades:

- dejar trazabilidad de acciones operativas
- almacenar actor, accion, resultado, nota, cambio de estado y metadata

Acciones observadas:

- `Creado`
- `Estado actualizado`
- `Llamada registrada`
- `Recordatorio actualizado`
- `Asignacion actualizada`
- `Asignado por round robin`
- `Importado`
- `Eliminado`

### 6.4 Entidades no existentes

Las siguientes entidades fueron solicitadas en el prompt como ejemplo, pero no existen en el codigo actual:

- `Clientes`
- `Propiedades`
- `Tareas`
- `Cotizaciones`
- `Conversion de lead` como entidad formal

Estas capacidades, si existen parcialmente, estan modeladas de forma simplificada dentro de `Lead` y `LeadLog`.

## 7. Sistema de Autenticacion y Roles

### Como inicia sesion un usuario

Flujo:

1. `GET /login` muestra el formulario Blade.
2. `POST /login` valida:
   - `email`
   - `password`
3. Se calcula rate limit por:
   - correo
   - IP
4. Se llama `Auth::attempt()` con:
   - `email`
   - `password`
   - `is_active = true`
5. Si el acceso es valido:
   - se limpian contadores del limiter
   - se regenera la sesion
   - se eliminan datos de impersonacion anteriores
   - se actualiza `last_login_at`
   - se redirige a `dashboard`
6. Si falla:
   - se incrementa limiter
   - se lanza error de validacion

### Middleware utilizado

- `guest`
  - restringe login a no autenticados
- `auth`
  - protege rutas autenticadas
- `active`
  - alias de `EnsureUserIsActive`
  - expulsa usuarios inactivos
- `role`
  - alias de `RequireRole`
  - restringe por uno o varios roles
- `RedirectToInstaller`
  - fuerza instalacion antes de operar
- `RedirectIfInstalled`
  - impide reabrir instalador tras instalar
- `ProtectInstallerAccess`
  - exige `INSTALLER_KEY` valida para el instalador

### Roles existentes

- `super_admin`
- `supervisor`
- `agent`

### Permisos por rol

#### `super_admin`

- ver todos los leads
- crear leads
- ver detalle y editar operativamente leads
- asignar leads
- ejecutar round robin
- ver metricas
- administrar usuarios
- resetear contrasenas
- impersonar usuarios
- eliminar leads logicamente

#### `supervisor`

- ver leads visibles segun equipo
- crear leads
- actualizar estado, llamadas y recordatorios de leads visibles
- asignar leads a agentes de su equipo
- ejecutar round robin sobre leads visibles
- ver metricas de su equipo
- no administra usuarios
- no elimina leads

#### `agent`

- crear leads
- ver solo leads asignados a si mismo
- actualizar estado, llamadas y recordatorios de sus leads visibles
- no gestiona asignaciones
- no ve metricas
- no administra usuarios
- no elimina leads

### Restricciones

- maximo `5` intentos fallidos por email o IP
- bloqueo temporal de `300` segundos
- solo usuarios activos pueden acceder
- no hay recuperacion automatica de contrasena en UI
- la impersonacion no permite entrar a:
  - uno mismo
  - super admins
  - usuarios inactivos

## 8. API

### Nota general

No existe una API REST JSON publica. Todos los endpoints actuales son rutas web que devuelven vistas HTML o redirects.

### 8.1 Home e instalacion

#### Metodo: `GET`
- Ruta: `/`
- Descripcion: redirige a login si el sistema esta instalado; si no, redirige al instalador.
- Parametros:
  - query opcional `installer_key`
- Respuesta:
  - redirect
- Middleware:
  - ninguno directo en la ruta

#### Metodo: `GET`
- Ruta: `/install`
- Descripcion: muestra el formulario de instalacion inicial.
- Parametros:
  - query o input `installer_key`
- Respuesta:
  - vista Blade
- Middleware:
  - `RedirectIfInstalled`
  - `ProtectInstallerAccess`

#### Metodo: `POST`
- Ruta: `/install`
- Descripcion: ejecuta la instalacion, escribe `.env`, corre migraciones y crea el super admin.
- Parametros:
  - `db_host`
  - `db_port`
  - `db_database`
  - `db_username`
  - `db_password`
  - `admin_name`
  - `admin_email`
  - `admin_password`
  - `admin_password_confirmation`
- Respuesta:
  - redirect al login o vuelta con errores
- Middleware:
  - `RedirectIfInstalled`
  - `ProtectInstallerAccess`

### 8.2 Autenticacion

#### Metodo: `GET`
- Ruta: `/login`
- Descripcion: muestra formulario de acceso.
- Parametros: ninguno
- Respuesta: vista Blade
- Middleware:
  - `RedirectToInstaller`
  - `guest`

#### Metodo: `POST`
- Ruta: `/login`
- Descripcion: procesa inicio de sesion.
- Parametros:
  - `email`
  - `password`
  - `remember` opcional
- Respuesta:
  - redirect a dashboard o error de validacion
- Middleware:
  - `RedirectToInstaller`
  - `guest`

#### Metodo: `POST`
- Ruta: `/logout`
- Descripcion: cierra sesion actual.
- Parametros: ninguno
- Respuesta: redirect al login
- Middleware:
  - `RedirectToInstaller`
  - `auth`

### 8.3 Leads

#### Metodo: `GET`
- Ruta: `/leads`
- Descripcion: listado de leads con filtro por busqueda y estado.
- Parametros:
  - query `search`
  - query `status`
- Respuesta: vista Blade paginada
- Middleware:
  - `RedirectToInstaller`
  - `auth`
  - `active`

#### Metodo: `GET`
- Ruta: `/leads/create`
- Descripcion: formulario de alta manual de lead.
- Parametros: ninguno
- Respuesta: vista Blade
- Middleware:
  - `RedirectToInstaller`
  - `auth`
  - `active`

#### Metodo: `POST`
- Ruta: `/leads`
- Descripcion: crea un lead manualmente.
- Parametros:
  - `name`
  - `phone`
  - `email`
  - `city`
  - `type`
  - `source`
  - `status`
- Respuesta: redirect al detalle
- Middleware:
  - `RedirectToInstaller`
  - `auth`
  - `active`

#### Metodo: `GET`
- Ruta: `/leads/{lead}`
- Descripcion: muestra el detalle del lead con historial y formularios operativos.
- Parametros:
  - route `lead`
- Respuesta: vista Blade
- Middleware:
  - `RedirectToInstaller`
  - `auth`
  - `active`

#### Metodo: `POST`
- Ruta: `/leads/{lead}/status`
- Descripcion: actualiza el estado del lead.
- Parametros:
  - route `lead`
  - `status`
  - `note`
- Respuesta: redirect al detalle
- Middleware:
  - `RedirectToInstaller`
  - `auth`
  - `active`

#### Metodo: `POST`
- Ruta: `/leads/{lead}/interactions`
- Descripcion: registra llamada o interaccion.
- Parametros:
  - route `lead`
  - `result`
  - `note`
- Respuesta: redirect al detalle
- Middleware:
  - `RedirectToInstaller`
  - `auth`
  - `active`

#### Metodo: `POST`
- Ruta: `/leads/{lead}/reminder`
- Descripcion: actualiza el recordatorio del lead.
- Parametros:
  - route `lead`
  - `reminder_at`
- Respuesta: redirect al detalle
- Middleware:
  - `RedirectToInstaller`
  - `auth`
  - `active`

#### Metodo: `POST`
- Ruta: `/leads/{lead}/assign`
- Descripcion: asigna o desasigna un lead.
- Parametros:
  - route `lead`
  - `assigned_user_id`
- Respuesta: redirect al detalle
- Middleware:
  - `RedirectToInstaller`
  - `auth`
  - `active`

#### Metodo: `POST`
- Ruta: `/leads/round-robin`
- Descripcion: distribuye leads sin asignar entre agentes seleccionados.
- Parametros:
  - `agent_ids[]`
  - `search`
  - `status`
- Respuesta:
  - redirect al listado con mensaje de estado o error
- Middleware:
  - `RedirectToInstaller`
  - `auth`
  - `active`

#### Metodo: `DELETE`
- Ruta: `/leads/{lead}`
- Descripcion: elimina logicamente un lead.
- Parametros:
  - route `lead`
- Respuesta: redirect al listado
- Middleware:
  - `RedirectToInstaller`
  - `auth`
  - `active`
  - `role:super_admin`

### 8.4 Importacion de leads

#### Metodo: `GET`
- Ruta: `/imports/leads`
- Descripcion: muestra modulo de importacion.
- Parametros: ninguno
- Respuesta: vista Blade
- Middleware:
  - `RedirectToInstaller`
  - `auth`
  - `active`

#### Metodo: `POST`
- Ruta: `/imports/leads/preview`
- Descripcion: sube archivo y genera preview con mapeo sugerido.
- Parametros:
  - archivo `file`
- Respuesta:
  - redirect al modulo con preview en sesion
- Middleware:
  - `RedirectToInstaller`
  - `auth`
  - `active`

#### Metodo: `POST`
- Ruta: `/imports/leads/confirm`
- Descripcion: confirma importacion usando mapping seleccionado.
- Parametros:
  - `mapping[field]`
- Respuesta:
  - redirect al listado de leads
- Middleware:
  - `RedirectToInstaller`
  - `auth`
  - `active`

### 8.5 Metricas

#### Metodo: `GET`
- Ruta: `/metrics`
- Descripcion: muestra metricas operativas por agente y pipeline.
- Parametros:
  - query `period` con valores `today`, `7d`, `30d`
- Respuesta:
  - vista Blade
- Middleware:
  - `RedirectToInstaller`
  - `auth`
  - `active`
  - `role:super_admin,supervisor`

### 8.6 Usuarios e impersonacion

#### Metodo: `GET`
- Ruta: `/users`
- Descripcion: listado de usuarios.
- Parametros: ninguno
- Respuesta: vista Blade
- Middleware:
  - `RedirectToInstaller`
  - `auth`
  - `active`
  - `role:super_admin`

#### Metodo: `GET`
- Ruta: `/users/create`
- Descripcion: formulario de alta de usuario.
- Parametros: ninguno
- Respuesta: vista Blade
- Middleware:
  - `RedirectToInstaller`
  - `auth`
  - `active`
  - `role:super_admin`

#### Metodo: `POST`
- Ruta: `/users`
- Descripcion: crea usuario.
- Parametros:
  - `name`
  - `email`
  - `password`
  - `password_confirmation`
  - `role`
  - `supervisor_id`
  - `is_active`
- Respuesta: redirect al listado
- Middleware:
  - `RedirectToInstaller`
  - `auth`
  - `active`
  - `role:super_admin`

#### Metodo: `GET`
- Ruta: `/users/{user}/edit`
- Descripcion: formulario de edicion.
- Parametros:
  - route `user`
- Respuesta: vista Blade
- Middleware:
  - `RedirectToInstaller`
  - `auth`
  - `active`
  - `role:super_admin`

#### Metodo: `PUT`
- Ruta: `/users/{user}`
- Descripcion: actualiza datos del usuario.
- Parametros:
  - route `user`
  - `name`
  - `email`
  - `role`
  - `supervisor_id`
  - `is_active`
- Respuesta: redirect a edit
- Middleware:
  - `RedirectToInstaller`
  - `auth`
  - `active`
  - `role:super_admin`

#### Metodo: `POST`
- Ruta: `/users/{user}/reset-password`
- Descripcion: resetea contrasena manualmente.
- Parametros:
  - route `user`
  - `password`
  - `password_confirmation`
- Respuesta: redirect a edit
- Middleware:
  - `RedirectToInstaller`
  - `auth`
  - `active`
  - `role:super_admin`

#### Metodo: `POST`
- Ruta: `/users/{user}/impersonate`
- Descripcion: entra como otro usuario.
- Parametros:
  - route `user`
- Respuesta: redirect a dashboard
- Middleware:
  - `RedirectToInstaller`
  - `auth`
  - `active`
  - `role:super_admin`

#### Metodo: `POST`
- Ruta: `/impersonation/leave`
- Descripcion: restaura la sesion original del impersonador.
- Parametros: ninguno
- Respuesta: redirect a usuarios o dashboard
- Middleware:
  - `RedirectToInstaller`
  - `auth`
  - `active`

## 9. Frontend

### Rutas y vistas

Vistas principales:

- `resources/views/auth/login.blade.php`
- `resources/views/installer/create.blade.php`
- `resources/views/leads/index.blade.php`
- `resources/views/leads/create.blade.php`
- `resources/views/leads/show.blade.php`
- `resources/views/leads/import.blade.php`
- `resources/views/users/index.blade.php`
- `resources/views/users/create.blade.php`
- `resources/views/users/edit.blade.php`
- `resources/views/metrics/index.blade.php`

### Layouts

- `resources/views/layouts/app.blade.php`
  - incluye `partials/shell-app`
- `resources/views/layouts/guest.blade.php`
  - incluye `partials/shell-guest`
- Component aliases necesarios para `x-layouts.*`:
  - `resources/views/components/layouts/app.blade.php`
  - `resources/views/components/layouts/guest.blade.php`

### Shell visual interno

- `partials/shell-app.blade.php`
  - contiene estilos globales del CRM interno
  - define header, toolbar, navegacion y contenedores
  - incluye banner de impersonacion

### Shell visual publico

- `partials/shell-guest.blade.php`
  - contiene estilos del login e instalador
  - sirve de wrapper para vistas guest

### Componentes reutilizables

- `x-ui.alert`
- `x-ui.badge`
- `x-ui.button`
- `x-ui.card`
- `x-ui.empty-state`
- `x-ui.page-header`
- `x-auth.login-panel`

### Formularios principales

- login
- instalacion
- alta manual de lead
- cambio de estado
- registro de llamada
- recordatorio
- asignacion
- importacion preview
- importacion confirm
- alta/edicion de usuarios
- reset de contrasena

### Estado global

- No existe estado global frontend tipo Redux, Pinia, Vuex o Context API.
- El estado vive en:
  - sesion Laravel
  - base de datos
  - `old()` de formularios
  - `flash messages`
  - `errors` de validacion

### Manejo de sesiones

- Laravel `session` guard
- remember me mediante `remember_token`
- datos de impersonacion en sesion:
  - `impersonator_id`
  - `impersonator_name`
- preview de importacion en sesion:
  - `lead_import_preview`
- acceso al instalador en sesion:
  - `installer_access_granted`

### Manejo de errores

- errores de validacion Laravel
- mensajes `withErrors()`
- mensajes `with('status', ...)`
- errores de instalacion e importacion por `RuntimeException`
- respuestas `403`, `404` o redirects segun el caso

## 10. Flujos Principales

### 10.1 Alta de usuario

1. `super_admin` entra a `/users/create`.
2. Captura nombre, email, rol, supervisor y contrasena.
3. Si el rol es `agent`, debe elegir un supervisor valido.
4. El sistema crea el usuario.
5. El usuario queda disponible para login inmediato si esta activo.

### 10.2 Inicio de sesion

1. Usuario abre `/login`.
2. Captura email y contrasena.
3. El sistema aplica rate limit por email e IP.
4. Solo autentica si `is_active = true`.
5. Regenera sesion y actualiza `last_login_at`.
6. Redirige a `/dashboard`, que redirige a `/leads`.

### 10.3 Creacion de cliente

- No existe flujo de `cliente` como entidad separada.
- En este CRM MVP, el prospecto/cliente potencial es directamente un `Lead`.

### 10.4 Creacion de lead

1. Usuario autenticado abre `/leads/create`.
2. Captura datos minimos.
3. Debe aportar al menos `name` o `phone`.
4. Elige estado inicial.
5. El sistema crea el registro en `leads`.
6. Se registra un `LeadLog` con accion `Creado`.
7. Redirige a la vista detalle.

### 10.5 Importacion de leads

1. Usuario abre `/imports/leads`.
2. Sube `CSV`, `TXT` o `XLSX`.
3. `LeadImportService` guarda temporalmente el archivo.
4. El sistema detecta headers y propone mapping.
5. El preview queda en sesion.
6. Usuario confirma mapping.
7. El servicio crea leads validos e ignora filas vacias.
8. Cada lead importado genera `LeadLog` accion `Importado`.
9. Se elimina el archivo temporal.

### 10.6 Conversion de lead

- No existe un flujo formal de conversion a cliente, venta, propiedad o cotizacion.
- La progresion comercial se modela solo con:
  - `status`
  - `lead_logs`

### 10.7 Seguimiento

1. Usuario abre detalle del lead.
2. Puede:
  - cambiar estado
  - registrar llamada
  - programar recordatorio
  - asignar responsable si tiene permiso
3. Cada accion genera un registro en `lead_logs`.
4. La vista historial muestra la secuencia completa.

### 10.8 Asignacion manual

1. `super_admin` o `supervisor` abre detalle del lead.
2. Elige agente permitido.
3. El sistema valida que el agente este dentro del conjunto asignable para el rol.
4. Actualiza `assigned_user_id`.
5. Registra log `Asignacion actualizada`.

### 10.9 Round robin

1. `super_admin` o `supervisor` filtra leads en index.
2. Selecciona agentes.
3. Ejecuta round robin.
4. El sistema toma leads visibles sin asignar.
5. Usa `lockForUpdate()` dentro de transaccion para evitar doble asignacion concurrente.
6. Reparte leads secuencialmente entre agentes seleccionados.
7. Registra `LeadLog` por cada lead.

### 10.10 Reportes

1. `super_admin` o `supervisor` abre `/metrics`.
2. Selecciona periodo `today`, `7d` o `30d`.
3. El sistema calcula:
  - total de leads visibles
  - sin asignar
  - recordatorios vencidos
  - pipeline visible por estado
  - llamadas por agente
  - leads trabajados
  - resultados de llamadas
  - pipeline por agente
  - leads sin seguimiento
  - recordatorios vencidos por agente

### 10.11 Instalacion inicial

1. Operador accede a `/install` con `INSTALLER_KEY`.
2. Captura credenciales MySQL y cuenta super admin.
3. `InstallerService` valida escritura en `.env`.
4. Valida conexion a DB.
5. Escribe/actualiza `.env`.
6. Reconfigura conexion runtime.
7. Ejecuta migraciones.
8. Crea o actualiza el super admin.
9. Escribe `installed.lock`.
10. Limpia cache y redirige a login.

## 11. Variables de Entorno

### Variables del proyecto y runtime principal

| Variable | Proposito | Donde se usa |
|---|---|---|
| `APP_NAME` | nombre de la aplicacion | `config/app.php`, `config/session.php`, `config/mail.php` |
| `APP_ENV` | entorno | `config/app.php` |
| `APP_KEY` | clave de cifrado Laravel | `config/app.php`, instalador |
| `APP_DEBUG` | debug | `config/app.php` |
| `APP_URL` | URL base | `config/app.php`, `filesystems.php`, `mail.php`, instalador |
| `APP_LOCALE` | locale principal | `config/app.php` |
| `APP_FALLBACK_LOCALE` | locale fallback | `config/app.php` |
| `APP_FAKER_LOCALE` | locale faker | `config/app.php` |
| `APP_MAINTENANCE_DRIVER` | mantenimiento | `config/app.php` |
| `INSTALLER_KEY` | protege el instalador web | `config/crm.php`, `ProtectInstallerAccess` |
| `BCRYPT_ROUNDS` | costo hashing | `config/hashing.php` scaffold Laravel |
| `LOG_CHANNEL` | canal default de logs | `config/logging.php` |
| `LOG_STACK` | stack de logging | `config/logging.php` |
| `LOG_DEPRECATIONS_CHANNEL` | canal deprecations | `config/logging.php` |
| `LOG_LEVEL` | nivel log | `config/logging.php` |
| `DB_CONNECTION` | driver DB | `config/database.php`, instalador |
| `DB_HOST` | host DB | `config/database.php`, instalador |
| `DB_PORT` | puerto DB | `config/database.php`, instalador |
| `DB_DATABASE` | nombre DB | `config/database.php`, instalador |
| `DB_USERNAME` | usuario DB | `config/database.php`, instalador |
| `DB_PASSWORD` | password DB | `config/database.php`, instalador |
| `SESSION_DRIVER` | driver de sesion | `config/session.php`, instalador |
| `SESSION_LIFETIME` | tiempo de sesion | `config/session.php` |
| `SESSION_ENCRYPT` | cifrado de sesion | `config/session.php` |
| `SESSION_PATH` | path cookie | `config/session.php` |
| `SESSION_DOMAIN` | dominio cookie | `config/session.php` |
| `BROADCAST_CONNECTION` | broadcasting | `config/broadcasting.php` scaffold |
| `FILESYSTEM_DISK` | disk default | `config/filesystems.php`, instalador |
| `QUEUE_CONNECTION` | queue default | `config/queue.php`, instalador |
| `CACHE_STORE` | cache default | `config/cache.php`, instalador |
| `MAIL_MAILER` | mailer por defecto | `config/mail.php` |
| `MAIL_SCHEME` | esquema SMTP | `config/mail.php` |
| `MAIL_HOST` | host SMTP | `config/mail.php` |
| `MAIL_PORT` | puerto SMTP | `config/mail.php` |
| `MAIL_USERNAME` | usuario SMTP | `config/mail.php` |
| `MAIL_PASSWORD` | password SMTP | `config/mail.php` |
| `MAIL_FROM_ADDRESS` | remitente correo | `config/mail.php` |
| `MAIL_FROM_NAME` | nombre remitente | `config/mail.php` |
| `AWS_ACCESS_KEY_ID` | credenciales AWS | `filesystems.php`, `services.php`, `queue.php` |
| `AWS_SECRET_ACCESS_KEY` | credenciales AWS | `filesystems.php`, `services.php`, `queue.php` |
| `AWS_DEFAULT_REGION` | region AWS | `filesystems.php`, `services.php`, `queue.php` |
| `AWS_BUCKET` | bucket S3 | `filesystems.php` |
| `AWS_USE_PATH_STYLE_ENDPOINT` | S3 path style | `filesystems.php` |
| `VITE_APP_NAME` | nombre expuesto a Vite | frontend build |

### Variables opcionales Laravel presentes en configuracion

Estas variables estan referenciadas por configuraciones base del framework, aunque el CRM actual no depende activamente de ellas en su flujo principal:

| Variable | Proposito | Donde se usa |
|---|---|---|
| `AUTH_GUARD` | guard default | `config/auth.php` |
| `AUTH_PASSWORD_BROKER` | broker password reset | `config/auth.php` |
| `AUTH_MODEL` | modelo auth | `config/auth.php` |
| `AUTH_PASSWORD_RESET_TOKEN_TABLE` | tabla reset tokens | `config/auth.php` |
| `AUTH_PASSWORD_TIMEOUT` | timeout confirmacion | `config/auth.php` |
| `SESSION_EXPIRE_ON_CLOSE` | expirar al cerrar navegador | `config/session.php` |
| `SESSION_CONNECTION` | conexion para sesiones DB | `config/session.php` |
| `SESSION_TABLE` | tabla sesiones | `config/session.php` |
| `SESSION_STORE` | store para sesiones cache-driven | `config/session.php` |
| `SESSION_SECURE_COOKIE` | cookie solo HTTPS | `config/session.php` |
| `SESSION_HTTP_ONLY` | cookie HTTP only | `config/session.php` |
| `SESSION_SAME_SITE` | same-site cookie | `config/session.php` |
| `SESSION_PARTITIONED_COOKIE` | partitioned cookie | `config/session.php` |
| `MAIL_URL` | URL mailer | `config/mail.php` |
| `MAIL_EHLO_DOMAIN` | EHLO SMTP | `config/mail.php` |
| `MAIL_SENDMAIL_PATH` | path sendmail | `config/mail.php` |
| `MAIL_LOG_CHANNEL` | canal log mail | `config/mail.php` |
| `POSTMARK_API_KEY` | Postmark | `config/services.php` |
| `RESEND_API_KEY` | Resend | `config/services.php` |
| `SLACK_BOT_USER_OAUTH_TOKEN` | Slack notifications | `config/services.php` |
| `SLACK_BOT_USER_DEFAULT_CHANNEL` | canal Slack | `config/services.php` |
| `AWS_URL` | URL S3 | `config/filesystems.php` |
| `AWS_ENDPOINT` | endpoint S3 compatible | `config/filesystems.php` |
| `DB_URL` | URL compuesta DB | `config/database.php` |
| `DB_FOREIGN_KEYS` | FKs SQLite | `config/database.php` |
| `DB_SOCKET` | socket MySQL | `config/database.php` |
| `DB_CHARSET` | charset DB | `config/database.php` |
| `DB_COLLATION` | collation DB | `config/database.php` |
| `MYSQL_ATTR_SSL_CA` | SSL CA MySQL | `config/database.php` |
| `DB_SSLMODE` | SSL mode Postgres | `config/database.php` |
| `DB_ENCRYPT` | SQL Server encrypt | `config/database.php` |
| `DB_TRUST_SERVER_CERTIFICATE` | SQL Server trust cert | `config/database.php` |
| `REDIS_CLIENT` | cliente Redis | `config/database.php` |
| `REDIS_HOST` | host Redis | `config/database.php` |
| `REDIS_PASSWORD` | password Redis | `config/database.php` |
| `REDIS_PORT` | puerto Redis | `config/database.php` |
| `REDIS_USERNAME` | usuario Redis | `config/database.php` |
| `REDIS_URL` | URL Redis | `config/database.php` |
| `REDIS_DB` | DB Redis default | `config/database.php` |
| `REDIS_CACHE_DB` | DB Redis cache | `config/database.php` |
| `REDIS_CLUSTER` | cluster Redis | `config/database.php` |
| `REDIS_PREFIX` | prefijo Redis | `config/database.php` |
| `REDIS_PERSISTENT` | Redis persistente | `config/database.php` |
| `REDIS_MAX_RETRIES` | retries Redis | `config/database.php` |
| `REDIS_BACKOFF_ALGORITHM` | backoff Redis | `config/database.php` |
| `REDIS_BACKOFF_BASE` | backoff base Redis | `config/database.php` |
| `REDIS_BACKOFF_CAP` | backoff cap Redis | `config/database.php` |
| `MEMCACHED_HOST` | host Memcached | `config/cache.php` |
| `MEMCACHED_PORT` | puerto Memcached | `config/cache.php` |
| `MEMCACHED_USERNAME` | usuario Memcached | `config/cache.php` |
| `MEMCACHED_PASSWORD` | password Memcached | `config/cache.php` |
| `MEMCACHED_PERSISTENT_ID` | persistent id | `config/cache.php` |
| `DB_CACHE_CONNECTION` | cache DB connection | `config/cache.php` |
| `DB_CACHE_TABLE` | tabla cache DB | `config/cache.php` |
| `DB_CACHE_LOCK_CONNECTION` | lock cache DB connection | `config/cache.php` |
| `DB_CACHE_LOCK_TABLE` | lock cache DB table | `config/cache.php` |
| `CACHE_STORAGE_DISK` | cache file disk | `config/cache.php` |
| `CACHE_STORAGE_PATH` | cache file path | `config/cache.php` |
| `DB_QUEUE_CONNECTION` | queue DB connection | `config/queue.php` |
| `DB_QUEUE_TABLE` | queue DB table | `config/queue.php` |
| `DB_QUEUE` | nombre queue DB | `config/queue.php` |
| `DB_QUEUE_RETRY_AFTER` | retry queue DB | `config/queue.php` |
| `BEANSTALKD_QUEUE_HOST` | host Beanstalkd | `config/queue.php` |
| `BEANSTALKD_QUEUE` | cola Beanstalkd | `config/queue.php` |
| `BEANSTALKD_QUEUE_RETRY_AFTER` | retry Beanstalkd | `config/queue.php` |
| `SQS_PREFIX` | prefijo SQS | `config/queue.php` |
| `SQS_QUEUE` | queue SQS | `config/queue.php` |
| `SQS_SUFFIX` | suffix SQS | `config/queue.php` |
| `REDIS_QUEUE_CONNECTION` | Redis queue connection | `config/queue.php` |
| `REDIS_QUEUE` | queue Redis | `config/queue.php` |
| `REDIS_QUEUE_RETRY_AFTER` | retry Redis queue | `config/queue.php` |
| `QUEUE_FAILED_DRIVER` | driver failed jobs | `config/queue.php` |
| `PAPERTRAIL_URL` | Papertrail logging | `config/logging.php` |
| `PAPERTRAIL_PORT` | Papertrail port | `config/logging.php` |
| `LOG_SLACK_WEBHOOK_URL` | Slack logging | `config/logging.php` |
| `LOG_SLACK_USERNAME` | usuario Slack logging | `config/logging.php` |
| `LOG_SLACK_EMOJI` | emoji Slack logging | `config/logging.php` |
| `LOG_DAILY_DAYS` | dias log daily | `config/logging.php` |
| `LOG_STDERR_FORMATTER` | formatter stderr | `config/logging.php` |
| `LOG_SYSLOG_FACILITY` | facility syslog | `config/logging.php` |
| `LOG_DEPRECATIONS_TRACE` | trace deprecations | `config/logging.php` |

## 12. Servicios Externos

### Integraciones reales activas

- Ninguna en el flujo actual del CRM.

### Correo

- Laravel Mail esta configurado pero no se usa para una funcionalidad de negocio activa.
- El mailer por defecto es `log`.
- No hay envio real de:
  - recordatorios
  - recuperacion de contrasena expuesta al usuario
  - notificaciones de negocio

### WhatsApp

- No existe integracion.

### Google

- No existe integracion.

### Stripe

- No existe integracion.

### Twilio

- No existe integracion.

### AWS

- Existen variables y configuracion base para `S3` y `SES`.
- No hay uso activo desde controladores o servicios del CRM.

### Azure

- No existe integracion.

### OpenAI

- No existe integracion.

### Supabase

- No existe integracion.

### Firebase

- No existe integracion.

### Slack

- Hay configuracion opcional de servicios/logging, pero no hay envio funcional de notificaciones del CRM.

## 13. Seguridad

### Validaciones

- Login:
  - email RFC
  - password requerida
- Instalacion:
  - DB host/port/database/username
  - admin email
  - admin password confirmada
- Leads:
  - nombre o telefono requeridos al menos uno
  - email RFC opcional
  - estado en lista permitida
- Interacciones:
  - resultado en lista permitida
- Recordatorios:
  - fecha valida
- Usuarios:
  - email unico
  - password minima 8
  - rol en lista permitida
  - supervisor valido para agentes
- Importacion:
  - archivo `csv`, `txt`, `xlsx`
  - maximo `10 MB`
  - maximo `5000` filas logicas

### Control de acceso

- `auth`
- `guest`
- `active`
- `role`
- reglas de visibilidad por rol en `Lead::scopeVisibleTo()`
- reglas especificas adicionales en controlador, por ejemplo borrado solo super admin

### Proteccion CSRF

- Formularios Blade usan `@csrf`

### Rate limiting

- Login:
  - 5 intentos maximos
  - por email
  - por IP
  - 300 segundos de bloqueo

### Sanitizacion

- Laravel escapa salida Blade por defecto
- Inputs se trimean en varios `FormRequest`
- `LeadImportService` normaliza headers y mapeos

### Riesgos detectados

- No hay notificacion activa de recordatorios; riesgo operativo alto.
- La recuperacion de contrasena existe a nivel framework pero no como flujo productivo visible.
- `login.blade.php` inserta inline el contenido SVG si existe el archivo; si el archivo fuera modificado con contenido no confiable, podria introducir riesgo XSS. En el contexto actual parece ser un recurso controlado del proyecto.
- La mayor parte de las reglas de negocio vive en controladores, lo que incrementa el riesgo de regresiones al crecer.
- Los valores de `status` y `result` son texto libre controlado por config, no por tablas catalogo.
- El proyecto casi no tiene pruebas automatizadas reales.

## 14. Deuda Tecnica

### Codigo duplicado

- `LeadController` repite logica de filtros entre `index()` y `baseVisibleLeadQuery()`.
- Los estilos globales viven duplicados en los shells Blade en lugar de un sistema CSS centralizado.
- Las validaciones de roles/permisos se distribuyen entre middleware, modelo y controlador.

### Componentes grandes

- `LeadController` concentra demasiadas responsabilidades:
  - listado
  - creacion
  - detalle
  - cambio de estado
  - llamadas
  - recordatorios
  - asignacion
  - round robin
  - eliminacion
- `MetricsController` es grande y mezcla armado de dashboard con consultas analiticas.
- `shell-app.blade.php` y `shell-guest.blade.php` son muy grandes y contienen CSS acoplado.

### Posibles refactors

- extraer servicios de:
  - actualizacion de estado
  - asignacion manual
  - round robin
  - recordatorios
  - metricas
- mover enums a objetos de dominio o catalogos DB
- centralizar autorizacion con Policies/Gates
- extraer CSS a archivos versionados por Vite
- agregar DTOs o clases de consulta para metricas

### Riesgos de mantenimiento

- bajo nivel de pruebas
- alta dependencia de hosting compartido y ejecucion sincronica
- sesiones e instalacion han requerido depuracion manual en produccion
- importacion XLSX depende de entorno y `vendor` correcto

### Cuellos de botella

- importacion sincronica de archivos
- consultas analiticas de metricas sobre `lead_logs` pueden crecer
- round robin carga todos los leads sin asignar visibles en memoria
- ausencia de jobs/colas limita escalabilidad

## 15. Estado Actual

### Funcionalidades terminadas

- instalador web protegido por clave
- escritura automatica de `.env`
- migraciones iniciales
- creacion de super admin
- login con remember me
- rate limit de acceso
- control de usuario activo
- jerarquia supervisor/agente
- gestion de usuarios
- reset manual de contrasena
- impersonacion
- alta manual de leads
- listado con busqueda y filtro por estado
- detalle de lead
- cambio de estado
- registro de llamadas
- recordatorios basicos
- historial por lead
- asignacion manual
- round robin
- metricas operativas
- importacion `CSV/TXT/XLSX`
- borrado logico de leads
- rediseño visual general de vistas

### Funcionalidades en desarrollo o ajuste

- rediseño fino del login y shell guest
- estabilizacion en produccion sobre cPanel
- validacion del comportamiento de impersonacion entre sesiones
- revision del flujo de importacion cuando arroja `500`

### Funcionalidades pendientes

- notificaciones reales de recordatorios
- recuperacion de contrasena operativa para usuarios finales
- pruebas automatizadas serias
- API JSON publica si se requiere integracion
- entidades de negocio mas avanzadas:
  - clientes
  - propiedades
  - cotizaciones
  - conversion de lead
  - tareas/agenda

## 16. Recomendaciones

### Alta prioridad

- Corregir el defecto reportado de cambio y filtro por estado.
- Implementar recordatorios activos:
  - alerta interna
  - correo opcional
  - marca de notificado
- Resolver el error `500` del analisis/importacion de archivos.
- Agregar pruebas feature para:
  - login
  - visibilidad de leads
  - cambio de estado
  - asignacion
  - round robin
  - importacion

### Media prioridad

- Extraer logica de negocio de `LeadController`.
- Implementar Policies para autorizacion.
- Mover estilos inline a archivos frontend reales.
- Agregar tablas catalogo para estados y resultados si se prevé personalizacion.
- Mejorar observabilidad en produccion:
  - logging operacional
  - reportes de errores

### Baja prioridad

- Crear API REST o endpoints JSON internos.
- Activar integraciones externas reales.
- Añadir dashboards mas avanzados.
- Replantear SPA o frontend mas rico solo si el producto crece.

## 17. Contexto para otra IA

## CONTEXTO COMPLETO DEL CRM

Este proyecto es un CRM MVP llamado `CRM Puerta Principal`, construido en `Laravel 13` sobre `PHP 8.3`, con base de datos `MySQL`, ORM `Eloquent` y renderizado server-side mediante `Blade`. El sistema esta pensado para operar en hosting compartido `cPanel`, por lo que evita dependencias de colas, workers permanentes, websockets o frontend SPA. La instalacion se realiza desde un instalador web propio protegido por `INSTALLER_KEY`, que escribe el archivo `.env`, valida la conexion a la base de datos, ejecuta migraciones y crea la cuenta inicial de `super_admin`.

La aplicacion es un monolito MVC. Las rutas viven en `routes/web.php`. Los middlewares principales son `RedirectToInstaller`, `RedirectIfInstalled`, `ProtectInstallerAccess`, `auth`, `guest`, `active` y `role`. La autenticacion usa el guard `web` con sesiones Laravel. El login aplica rate limiting por email e IP, con 5 intentos maximos y bloqueo temporal de 5 minutos. Solo pueden iniciar sesion usuarios con `is_active = true`.

Las entidades de negocio reales son tres: `User`, `Lead` y `LeadLog`. `User` soporta roles `super_admin`, `supervisor` y `agent`, ademas de jerarquia mediante `supervisor_id`. `Lead` es la entidad central del negocio y almacena datos minimos del prospecto (`name`, `phone`, `email`, `city`, `type`, `source`), estado comercial (`status`), responsable (`assigned_user_id`), creador (`created_by`), recordatorio (`reminder_at`), ultimo contacto (`last_contact_at`) y metadata de importacion. `LeadLog` es la bitacora cronologica del lead y registra acciones como creacion, cambio de estado, llamadas, recordatorios, asignaciones, importacion y eliminacion.

La visibilidad de leads depende del rol y esta implementada en `Lead::scopeVisibleTo(User $user)`. Un `super_admin` ve todo. Un `supervisor` ve leads asignados a agentes activos de su equipo y tambien leads sin asignar creados por el o por su equipo. Un `agent` solo ve leads asignados a el. Esto significa que muchas consultas del sistema deben usar `Lead::query()->visibleTo($user)` para respetar la seguridad funcional. Los leads eliminados usan `SoftDeletes`, por lo que no se borran fisicamente sino que se excluyen de consultas normales mediante `deleted_at`.

Los procesos funcionales implementados son: instalacion inicial, login/logout, alta manual de leads, listado con busqueda y filtro por estado, detalle de lead, cambio de estado, registro de llamadas, recordatorios, historial, asignacion manual, round robin, importacion de leads y metricas. Los estados del pipeline y los resultados de llamada estan definidos en `config/crm.php`, no en tablas. Estados de lead: `Nuevo`, `Por llamar`, `No contesta`, `Contactado`, `Interesado`, `Cita agendada`, `Cerrado`, `Perdido`. Resultados de interaccion: `Llamada realizada`, `No contesta`, `Buzon`, `Whatsapp enviado`, `Correo enviado`, `Interesado`, `No interesado`, `Seguimiento`.

El controlador mas importante es `LeadController`. Actualmente concentra muchas responsabilidades: index, create, store, show, updateStatus, storeInteraction, updateReminder, assign, roundRobin y destroy. Cuando cambia el estado de un lead, el controlador actualiza `leads.status` y registra un `LeadLog` con `from_status` y `to_status`. Cuando se registra una llamada, actualiza `last_contact_at` y escribe `LeadLog`. Cuando se agrega un recordatorio, solo actualiza `reminder_at` y escribe un log; no existe notificacion activa por correo, push o job programado. Cuando se asigna un lead, cambia `assigned_user_id` y registra `LeadLog`. Cuando se ejecuta round robin, toma leads visibles sin asignar, los bloquea con `lockForUpdate()` dentro de una transaccion y los distribuye entre agentes permitidos.

La importacion masiva se implementa en `LeadImportController` y `LeadImportService`. El usuario sube un archivo `CSV`, `TXT` o `XLSX`; el servicio lo guarda temporalmente en `storage/app/imports/tmp`, detecta headers, sugiere mapping segun aliases configurados y guarda el preview en sesion (`lead_import_preview`). Al confirmar, recorre las filas, mapea columnas, ignora filas sin nombre y telefono, crea leads con `status = Nuevo`, guarda archivo origen y fecha de importacion, y registra `LeadLog` con accion `Importado`. El limite logico de importacion es `5000` filas y el limite de upload validado es `10 MB`. La lectura de `XLSX` depende de `openspout/openspout`.

Las metricas viven en `MetricsController` y son accesibles solo para `super_admin` y `supervisor`. Calculan total de leads visibles, leads sin asignar, recordatorios vencidos, resumen del pipeline y tabla por agente con llamadas, leads trabajados, resultados, pipeline, leads sin seguimiento y recordatorios vencidos. Estas metricas dependen de `lead_logs` y `leads`, y respetan visibilidad por rol.

La gestion de usuarios vive en `UserController`. Solo `super_admin` puede listar, crear y editar usuarios, resetear contrasenas e impersonar cuentas. Los agentes deben apuntar a un supervisor valido. Un super admin no puede desactivarse a si mismo ni quitarse su propio rol de super admin desde la UI. La impersonacion guarda `impersonator_id` e `impersonator_name` en sesion; luego hace `Auth::login($user)` para entrar a la cuenta destino. El sistema permite salir de impersonacion mediante una ruta dedicada.

El frontend es Blade server-rendered. Hay dos shells visuales principales: `partials/shell-app.blade.php` para la aplicacion interna y `partials/shell-guest.blade.php` para login e instalador. Existen componentes UI reutilizables (`alert`, `badge`, `button`, `card`, `empty-state`, `page-header`) y un panel auth reutilizable (`x-auth.login-panel`). Aunque `Tailwind` y `Vite` estan configurados, la mayor parte del estilo actual vive inline dentro de los shells Blade. `resources/js/app.js` no contiene logica significativa.

La base de datos tiene como tablas principales `users`, `leads` y `lead_logs`. Tambien existen tablas framework como `password_reset_tokens`, `sessions`, `cache`, `jobs`, etc. El instalador fija normalmente `SESSION_DRIVER=file`, `CACHE_STORE=file`, `QUEUE_CONNECTION=sync` y `FILESYSTEM_DISK=local`, porque el producto esta optimizado para hosting compartido. Existe un lock file de instalacion en `storage/app/installed.lock`.

Restricciones clave: no existe API REST JSON publica; no existen entidades separadas para clientes, propiedades, tareas o cotizaciones; la conversion de lead no esta modelada como flujo propio; no hay notificaciones reales de recordatorios; no hay colas activas ni procesos background; la cobertura de pruebas es minima. Riesgos actuales: incidencia reportada en actualizacion/filtrado por estado, error `500` pendiente en importacion, y comportamiento de sesion/impersonacion a validar. El estado del proyecto es de MVP funcional listo para operacion basica, con deuda tecnica moderada y necesidad de robustecer notificaciones, pruebas y separacion de responsabilidades antes de escalar.
