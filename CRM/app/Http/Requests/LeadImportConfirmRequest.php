<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LeadImportConfirmRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mapping' => ['nullable', 'array'],
            'mapping.*' => ['nullable', 'string', 'max:255'],
        ];
    }
}
