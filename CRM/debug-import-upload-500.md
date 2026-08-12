[OPEN] Debug Session: import-upload-500

## Síntoma
- Al subir un archivo en el CRM y pulsar "Analizar archivo", la app responde con `500 Server Error`.

## Hipótesis
1. El archivo temporal no puede guardarse en `storage/app/imports/tmp` por permisos o ruta faltante.
2. La validación pasa, pero la lectura del archivo (`csv` o `xlsx`) falla por dependencia o extensión PHP ausente.
3. El hosting rechaza el upload por límites de `upload_max_filesize` / `post_max_size` y el flujo rompe después.
4. El error ocurre solo para `xlsx` porque `OpenSpout` o una extensión asociada no está disponible en producción.
5. El fallo está en el request o sesión al guardar `lead_import_preview`, no en el parser.

## Evidencia Pendiente
- Falta el `laravel.log` fresco del intento de importación.
- Falta confirmar si el fallo ocurre con `csv`, `xlsx` o ambos.

## Siguiente Paso
- Revisar el log fresco y correlacionarlo con el flujo `LeadImportController` / `LeadImportService`.
