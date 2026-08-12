[OPEN] Debug Session: missing-app-key

## Síntoma
- Producción responde `500` al abrir `/install`
- `laravel.log` repite `No application encryption key has been specified.`

## Hipótesis
1. Laravel no está leyendo el archivo `.env` real del proyecto en producción.
2. El archivo `.env` existe, pero su contenido tiene formato/caracteres que impiden parsear `APP_KEY`.
3. El servidor está sirviendo otra copia/ruta del proyecto distinta a la carpeta donde se edita `.env`.
4. Existe una capa de caché o bootstrap residual fuera de `bootstrap/cache` que sigue arrancando con configuración inválida.
5. El log observado mezcla errores viejos y nuevos, y falta aislar un intento limpio para ver el estado actual real.

## Evidencia Recolectada
- `config/app.php` usa `env('APP_KEY')`.
- `bootstrap/cache/` ya fue vaciado.
- El log más reciente sigue mostrando `MissingAppKeyException`.
- Prueba runtime del servidor:
  - `base_path=/home/puertapr/crm.puertaprincipal.mx`
  - `env_file=yes`
  - `env_app_key=base64:JJ0Q1k/9bJFwGHx0Ow5M+rXpUZMpoRbWt72SzPcrs54=`
  - `config_app_key=base64:JJ0Q1k/9bJFwGHx0Ow5M+rXpUZMpoRbWt72SzPcrs54=`

## Estado De Hipótesis
1. Rechazada: Laravel no está leyendo el archivo `.env` real del proyecto.
2. Rechazada: `APP_KEY` no se está parseando desde `.env`.
3. Rechazada parcialmente: el `base_path` sí coincide con la ruta del proyecto esperado.
4. Pendiente: el `laravel.log` observado puede contener errores viejos previos a la corrección.
5. Pendiente: el `500` actual probablemente ya es otro error distinto y falta capturarlo en un intento limpio.

## Siguiente Paso
- Borrar o renombrar `storage/logs/laravel.log`, reproducir una sola vez el acceso a `/install`, y analizar únicamente el error nuevo generado.


