<?php

namespace App\Services\LeadImport;

use App\Models\Lead;
use App\Models\LeadLog;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use OpenSpout\Reader\Common\Creator\ReaderEntityFactory;
use RuntimeException;

class LeadImportService
{
    public function preview(UploadedFile $file): array
    {
        $storedPath = $file->storeAs(
            'imports/tmp',
            Str::uuid()->toString().'.'.$file->getClientOriginalExtension(),
        );

        if ($storedPath === false) {
            throw new RuntimeException('No fue posible guardar temporalmente el archivo de importacion.');
        }

        $absolutePath = storage_path('app/'.$storedPath);
        $dataset = $this->extractDataset($absolutePath, $file->getClientOriginalExtension(), 5);

        if ($dataset['total_rows'] === 0) {
            File::delete($absolutePath);
            throw new RuntimeException('El archivo no contiene registros para importar.');
        }

        if ($dataset['total_rows'] > (int) config('crm.import_max_rows', 5000)) {
            File::delete($absolutePath);
            throw new RuntimeException('El archivo excede el limite de 5000 registros y fue rechazado.');
        }

        return [
            'stored_path' => $storedPath,
            'original_name' => $file->getClientOriginalName(),
            'extension' => strtolower((string) $file->getClientOriginalExtension()),
            'headers' => $dataset['headers'],
            'sample_rows' => $dataset['sample_rows'],
            'total_rows' => $dataset['total_rows'],
            'suggested_mapping' => $this->suggestMapping($dataset['headers']),
        ];
    }

    public function import(array $previewState, array $mapping, int $userId): array
    {
        $absolutePath = storage_path('app/'.$previewState['stored_path']);

        if (! File::exists($absolutePath)) {
            throw new RuntimeException('El archivo temporal de importacion ya no existe. Vuelve a cargarlo.');
        }

        $dataset = $this->extractDataset($absolutePath, $previewState['extension']);
        $sanitizedMapping = $this->sanitizeMapping($mapping, $previewState['headers']);

        $imported = 0;
        $skipped = 0;

        foreach ($dataset['rows'] as $row) {
            $payload = $this->mapRow($row, $sanitizedMapping);

            if (trim((string) ($payload['name'] ?? '')) === '' && trim((string) ($payload['phone'] ?? '')) === '') {
                $skipped++;
                continue;
            }

            $lead = Lead::query()->create([
                'name' => $payload['name'] ?: null,
                'phone' => $payload['phone'] ?: null,
                'email' => $payload['email'] ?: null,
                'city' => $payload['city'] ?: null,
                'type' => $payload['type'] ?: null,
                'source' => $payload['source'] ?: null,
                'status' => Lead::STATUS_NEW,
                'created_by' => $userId,
                'import_file_name' => $previewState['original_name'],
                'imported_at' => now(),
            ]);

            LeadLog::query()->create([
                'lead_id' => $lead->id,
                'user_id' => $userId,
                'action' => 'Importado',
                'note' => 'Lead importado desde archivo.',
                'to_status' => Lead::STATUS_NEW,
                'meta_json' => [
                    'file_name' => $previewState['original_name'],
                ],
                'created_at' => now(),
            ]);

            $imported++;
        }

        if ($imported === 0) {
            throw new RuntimeException('No se encontraron filas validas para importar. Se requiere al menos nombre o telefono.');
        }

        File::delete($absolutePath);

        return [
            'imported' => $imported,
            'skipped' => $skipped,
        ];
    }

    public function clearPreview(?array $previewState): void
    {
        $storedPath = $previewState['stored_path'] ?? null;

        if ($storedPath === null) {
            return;
        }

        File::delete(storage_path('app/'.$storedPath));
    }

    private function extractDataset(string $path, string $extension, int $sampleLimit = 0): array
    {
        $extension = strtolower($extension);

        return match ($extension) {
            'csv', 'txt' => $this->extractCsvDataset($path, $sampleLimit),
            'xlsx' => $this->extractXlsxDataset($path, $sampleLimit),
            default => throw new RuntimeException('Formato de archivo no soportado para importacion.'),
        };
    }

    private function extractCsvDataset(string $path, int $sampleLimit = 0): array
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException('No fue posible abrir el archivo CSV.');
        }

        $headers = [];
        $rows = [];
        $sampleRows = [];
        $totalRows = 0;
        $delimiter = $this->detectCsvDelimiter($path);

        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            $data = array_map(fn ($value) => trim((string) $value), $data);

            if ($headers === []) {
                $headers = $this->normalizeHeaders($data);
                continue;
            }

            if ($this->rowIsEmpty($data)) {
                continue;
            }

            $row = $this->combineRow($headers, $data);
            $rows[] = $row;
            $totalRows++;

            if ($sampleLimit > 0 && count($sampleRows) < $sampleLimit) {
                $sampleRows[] = $row;
            }
        }

        fclose($handle);

        return [
            'headers' => $headers,
            'rows' => $rows,
            'sample_rows' => $sampleRows,
            'total_rows' => $totalRows,
        ];
    }

    private function extractXlsxDataset(string $path, int $sampleLimit = 0): array
    {
        if (! class_exists(ReaderEntityFactory::class)) {
            throw new RuntimeException('La dependencia para leer archivos XLSX no esta instalada. Ejecuta composer install.');
        }

        $reader = ReaderEntityFactory::createXLSXReader();
        $reader->open($path);

        $headers = [];
        $rows = [];
        $sampleRows = [];
        $totalRows = 0;

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $data = array_map(fn ($value) => trim((string) $value), $row->toArray());

                if ($headers === []) {
                    $headers = $this->normalizeHeaders($data);
                    continue;
                }

                if ($this->rowIsEmpty($data)) {
                    continue;
                }

                $mappedRow = $this->combineRow($headers, $data);
                $rows[] = $mappedRow;
                $totalRows++;

                if ($sampleLimit > 0 && count($sampleRows) < $sampleLimit) {
                    $sampleRows[] = $mappedRow;
                }
            }

            break;
        }

        $reader->close();

        return [
            'headers' => $headers,
            'rows' => $rows,
            'sample_rows' => $sampleRows,
            'total_rows' => $totalRows,
        ];
    }

    private function suggestMapping(array $headers): array
    {
        $aliases = config('crm.import_header_aliases', []);
        $suggested = [];

        foreach (array_keys(config('crm.import_target_fields', [])) as $field) {
            $suggested[$field] = '';
            $fieldAliases = $aliases[$field] ?? [];

            foreach ($headers as $header) {
                $normalizedHeader = $this->normalizeHeaderValue($header);

                foreach ($fieldAliases as $alias) {
                    if ($normalizedHeader === $this->normalizeHeaderValue($alias)) {
                        $suggested[$field] = $header;
                        break 2;
                    }
                }
            }
        }

        return $suggested;
    }

    private function sanitizeMapping(array $mapping, array $headers): array
    {
        $allowedHeaders = array_values($headers);
        $sanitized = [];

        foreach (array_keys(config('crm.import_target_fields', [])) as $field) {
            $selectedHeader = trim((string) ($mapping[$field] ?? ''));
            $sanitized[$field] = in_array($selectedHeader, $allowedHeaders, true) ? $selectedHeader : '';
        }

        return $sanitized;
    }

    private function mapRow(array $row, array $mapping): array
    {
        $payload = [];

        foreach ($mapping as $field => $header) {
            $payload[$field] = $header !== '' ? trim((string) ($row[$header] ?? '')) : '';
        }

        return $payload;
    }

    private function combineRow(array $headers, array $data): array
    {
        $row = [];

        foreach ($headers as $index => $header) {
            $row[$header] = $data[$index] ?? '';
        }

        return $row;
    }

    private function normalizeHeaders(array $headers): array
    {
        $normalized = [];
        $used = [];

        foreach ($headers as $index => $header) {
            $value = trim((string) $header);
            $value = $value !== '' ? $value : 'columna_'.($index + 1);

            if (! isset($used[$value])) {
                $used[$value] = 0;
                $normalized[] = $value;
                continue;
            }

            $used[$value]++;
            $normalized[] = $value.'_'.$used[$value];
        }

        return $normalized;
    }

    private function normalizeHeaderValue(string $value): string
    {
        $value = Str::lower(trim($value));
        $value = Str::ascii($value);
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? $value;

        return trim($value);
    }

    private function rowIsEmpty(array $data): bool
    {
        foreach ($data as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function detectCsvDelimiter(string $path): string
    {
        $sample = (string) file_get_contents($path, false, null, 0, 2048);
        $delimiters = [',', ';', "\t", '|'];
        $bestDelimiter = ',';
        $bestCount = 0;

        foreach ($delimiters as $delimiter) {
            $count = substr_count($sample, $delimiter);

            if ($count > $bestCount) {
                $bestCount = $count;
                $bestDelimiter = $delimiter;
            }
        }

        return $bestDelimiter;
    }
}
