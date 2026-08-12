<?php

namespace App\Services\Installer;

use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Throwable;

class InstallationStatus
{
    public function isInstalled(): bool
    {
        if (File::exists($this->lockFilePath())) {
            return true;
        }

        try {
            if (! Schema::hasTable('users')) {
                return false;
            }

            return User::query()
                ->where('role', User::ROLE_SUPER_ADMIN)
                ->exists();
        } catch (Throwable) {
            return false;
        }
    }

    public function markAsInstalled(array $payload = []): void
    {
        File::ensureDirectoryExists(dirname($this->lockFilePath()));
        File::put($this->lockFilePath(), json_encode([
            'installed_at' => now()->toIso8601String(),
            'app_name' => config('app.name'),
            'admin_email' => $payload['admin_email'] ?? null,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    public function lockFilePath(): string
    {
        return config('crm.installed_lock');
    }
}
