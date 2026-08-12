<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InstallApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'db_host' => ['required', 'string', 'max:255'],
            'db_port' => ['required', 'integer', 'between:1,65535'],
            'db_database' => ['required', 'string', 'max:255'],
            'db_username' => ['required', 'string', 'max:255'],
            'db_password' => ['nullable', 'string', 'max:255'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email:rfc', 'max:255'],
            'admin_password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'db_host' => trim((string) $this->input('db_host')),
            'db_database' => trim((string) $this->input('db_database')),
            'db_username' => trim((string) $this->input('db_username')),
            'admin_name' => trim((string) $this->input('admin_name')),
            'admin_email' => trim((string) $this->input('admin_email')),
            'db_port' => $this->filled('db_port') ? (int) $this->input('db_port') : 3306,
        ]);
    }
}
