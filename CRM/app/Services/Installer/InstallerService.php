<?php

namespace App\Services\Installer;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PDO;
use RuntimeException;
use Throwable;

class InstallerService
{
    public function __construct(private readonly InstallationStatus $installationStatus)
    {
    }

    public function install(array $data): void
    {
        $this->assertWritableEnvironment();
        $this->assertDatabaseConnection($data);

        $envValues = $this->buildEnvironmentValues($data);
        $this->writeEnvironmentFile($envValues);
        $this->reconfigureDatabase($envValues);

        $migrationExitCode = Artisan::call('migrate', ['--force' => true]);

        if ($migrationExitCode !== 0) {
            throw new RuntimeException('No fue posible ejecutar las migraciones iniciales.');
        }

        $existingAdmin = User::query()
            ->where('email', $data['admin_email'])
            ->first();

        if ($existingAdmin !== null && ! $existingAdmin->isSuperAdmin()) {
            throw new RuntimeException(
                'Ya existe un usuario con ese correo y no tiene rol de Super Admin. Usa otro correo o limpia la base de datos antes de instalar.',
            );
        }

        if ($existingAdmin === null) {
            User::query()->create([
                'name' => $data['admin_name'],
                'email' => $data['admin_email'],
                'password' => $data['admin_password'],
                'role' => User::ROLE_SUPER_ADMIN,
                'is_active' => true,
            ]);
        } else {
            $existingAdmin->update([
                'name' => $data['admin_name'],
                'password' => $data['admin_password'],
                'role' => User::ROLE_SUPER_ADMIN,
                'is_active' => true,
            ]);
        }

        $this->installationStatus->markAsInstalled([
            'admin_email' => $data['admin_email'],
        ]);

        Artisan::call('optimize:clear');
    }

    public function assertDatabaseConnection(array $data): void
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $data['db_host'],
            $data['db_port'],
            $data['db_database'],
        );

        try {
            new PDO($dsn, $data['db_username'], $data['db_password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5,
            ]);
        } catch (Throwable $exception) {
            throw new RuntimeException(
                'No fue posible conectar con la base de datos usando las credenciales proporcionadas.',
                0,
                $exception,
            );
        }
    }

    private function assertWritableEnvironment(): void
    {
        $envPath = base_path('.env');
        $basePath = base_path();

        if (File::exists($envPath) && ! is_writable($envPath)) {
            throw new RuntimeException('El archivo .env no tiene permisos de escritura.');
        }

        if (! File::exists($envPath) && ! is_writable($basePath)) {
            throw new RuntimeException('La carpeta del proyecto no tiene permisos para crear el archivo .env.');
        }
    }

    private function buildEnvironmentValues(array $data): array
    {
        return [
            'APP_NAME' => 'CRM Puerta Principal',
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
            'APP_URL' => url('/'),
            'APP_KEY' => $this->resolveAppKey(),
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $data['db_host'],
            'DB_PORT' => $data['db_port'],
            'DB_DATABASE' => $data['db_database'],
            'DB_USERNAME' => $data['db_username'],
            'DB_PASSWORD' => $data['db_password'],
            'SESSION_DRIVER' => 'file',
            'CACHE_STORE' => 'file',
            'QUEUE_CONNECTION' => 'sync',
            'FILESYSTEM_DISK' => 'local',
        ];
    }

    private function writeEnvironmentFile(array $values): void
    {
        $envPath = base_path('.env');
        $sourcePath = File::exists($envPath) ? $envPath : base_path('.env.example');
        $contents = File::get($sourcePath);

        foreach ($values as $key => $value) {
            $escaped = $this->escapeEnvironmentValue($value);
            $pattern = "/^{$key}=.*$/m";

            if (preg_match($pattern, $contents) === 1) {
                $contents = preg_replace($pattern, sprintf('%s=%s', $key, $escaped), $contents) ?? $contents;
                continue;
            }

            $contents .= PHP_EOL.sprintf('%s=%s', $key, $escaped);
        }

        File::put($envPath, trim($contents).PHP_EOL);
    }

    private function reconfigureDatabase(array $envValues): void
    {
        Config::set('database.default', 'mysql');
        Config::set('database.connections.mysql.host', $envValues['DB_HOST']);
        Config::set('database.connections.mysql.port', $envValues['DB_PORT']);
        Config::set('database.connections.mysql.database', $envValues['DB_DATABASE']);
        Config::set('database.connections.mysql.username', $envValues['DB_USERNAME']);
        Config::set('database.connections.mysql.password', $envValues['DB_PASSWORD']);
        Config::set('session.driver', 'file');
        Config::set('cache.default', 'file');
        Config::set('queue.default', 'sync');

        DB::purge('mysql');
        DB::reconnect('mysql');
    }

    private function resolveAppKey(): string
    {
        $configured = (string) config('app.key');

        if ($configured !== '') {
            return $configured;
        }

        return 'base64:'.base64_encode(random_bytes(32));
    }

    private function escapeEnvironmentValue(mixed $value): string
    {
        $stringValue = (string) $value;

        if ($stringValue === '') {
            return '""';
        }

        if (preg_match('/\\s/', $stringValue) === 1 || Str::contains($stringValue, ['#', '"', "'", '$'])) {
            return '"'.addcslashes($stringValue, "\"\\").'"';
        }

        return $stringValue;
    }
}
