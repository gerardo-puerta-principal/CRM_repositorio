# Despliegue En cPanel

Guia minima para publicar este CRM en hosting compartido.

## 1. Preparacion

- Crear la base de datos MySQL desde cPanel
- Crear usuario MySQL y asignarlo a la base
- Confirmar que el hosting tenga:
  - PHP `8.3+`
  - `pdo_mysql`
  - Composer o acceso para subir `vendor`

## 2. Subir archivos

- Subir el proyecto al servidor
- Si el hosting usa `public_html`, apuntar el subdominio `crm.puertaprincipal.mx` al directorio `public/`
- Si no es posible apuntar directamente a `public/`, crear una estructura segura con apoyo del hosting

## 3. Instalar dependencias

Si el servidor tiene Composer:

```bash
composer install --no-dev --optimize-autoloader
```

Si no tiene Composer:

- Ejecutar `composer install --no-dev --optimize-autoloader` en local
- Subir tambien la carpeta `vendor/`

## 4. Permisos necesarios

Dar permisos de escritura a:

- `.env`
- `storage/`
- `bootstrap/cache/`

## 5. Primer acceso

- Configurar `INSTALLER_KEY` en el `.env` antes de exponer el subdominio (clave larga y privada).
- Abrir el instalador usando una de estas rutas:
  - `crm.puertaprincipal.mx/?installer_key=TU_CLAVE`
  - `crm.puertaprincipal.mx/install?installer_key=TU_CLAVE`
- Completar el instalador web (si la clave es incorrecta, el instalador no se mostrara)
- Ingresar:
  - host
  - puerto
  - base de datos
  - usuario
  - contrasena
  - datos del `Super Admin`

## 6. Despues de instalar

- Verificar que `/install` ya no se pueda abrir (aunque se conozca la clave).
- Recomendacion: borrar `INSTALLER_KEY` del `.env` o dejarla vacia despues de instalar.

Si tienes terminal disponible, ejecutar:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Si no hay terminal:

- El CRM puede funcionar sin estos comandos
- Solo asegurate de que `.env` y `storage/` sean escribibles durante la instalacion

## 7. Recomendaciones operativas

- No subir archivos de importacion mayores a `5000` filas
- Usar archivos `CSV` cuando sea posible para menor consumo de memoria
- Mantener activo solo a usuarios que realmente operan el sistema
- Verificar periodicamente recordatorios vencidos desde el CRM
- Login: el sistema bloquea temporalmente despues de multiples intentos fallidos (anti fuerza bruta).

## 8. Problemas comunes

### El instalador no puede escribir `.env`

- Revisar permisos del archivo o del directorio del proyecto

### El archivo XLSX no importa

- Confirmar que `vendor/` este completo y que `openspout/openspout` exista

### Error de conexion a MySQL

- Validar host, puerto, base, usuario y contrasena desde cPanel

### Pantalla en blanco o error 500

- Revisar logs de Laravel en `storage/logs/`
- Confirmar version de PHP y extensiones habilitadas
