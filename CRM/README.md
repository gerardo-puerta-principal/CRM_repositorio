# CRM Puerta Principal

CRM web MVP construido con Laravel para operacion comercial en hosting compartido `cPanel`, sin colas, sin procesos en background y sin frontend framework.

## Alcance del MVP

- Instalador web en primer acceso
- Login simple con sesiones Laravel (con proteccion contra fuerza bruta)
- Roles: `super_admin`, `supervisor`, `agent`
- Captura manual de leads
- Importacion `CSV/XLSX` con autodeteccion de columnas
- Pipeline con historial simple e interacciones
- Asignacion manual y round robin
- Metricas basicas por agente y por estado
- Gestion de usuarios, reset manual de contrasena e impersonacion

## Requisitos

- PHP `8.3+`
- MySQL
- Extension `pdo_mysql`
- Composer
- Permisos de escritura en:
  - `.env`
  - `storage/`
  - `bootstrap/cache/`

## Instalacion local o servidor con terminal

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

Nota:
- En produccion normal del proyecto, el instalador web puede encargarse de `.env`, migraciones y `Super Admin`.
- Si usas el instalador web, no necesitas ejecutar migraciones manualmente antes del primer acceso.

## Flujo de instalacion web

Seguridad:
- El instalador esta protegido por una clave: `INSTALLER_KEY`.

1. Configurar `INSTALLER_KEY` en el `.env` (antes del primer acceso publico).
2. Abrir el sistema por primera vez usando `/?installer_key=TU_CLAVE` o `/install?installer_key=TU_CLAVE`.
2. Capturar credenciales MySQL y datos del `Super Admin`.
3. El sistema valida conexion, escribe `.env`, ejecuta migraciones y crea la cuenta inicial.
4. Se genera el archivo `installed.lock`.
5. El instalador queda bloqueado y redirige al login.

## Restricciones tecnicas

- Importacion maxima: `5000` registros por archivo
- Si el archivo supera el limite, se rechaza automaticamente
- No hay colas, jobs, websockets ni procesos asincornos
- La importacion corre de forma sincronica

## Reglas operativas y seguridad (pre-produccion)

- Login: maximo `5` intentos fallidos por correo o por IP, con bloqueo de `5` minutos.
- Supervisores: pueden ver leads asignados a su equipo; y de los no asignados, solo los creados por ellos o por sus agentes.
- Round robin: protegido contra concurrencia (dos ejecuciones simultaneas no deben asignar el mismo lead dos veces).

## Modulos principales

- `Leads`
- `Pipeline`
- `Interacciones`
- `Importacion`
- `Asignacion`
- `Usuarios`
- `Metricas`

## Despliegue en cPanel

Consulta la guia detallada en `DEPLOY-CPANEL.md`.
