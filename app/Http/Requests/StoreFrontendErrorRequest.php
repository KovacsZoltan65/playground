<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFrontendErrorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'max:100'],
            'message' => ['required', 'string', 'max:5000'],
            'url' => ['nullable', 'string', 'max:2048'],
            'component' => ['nullable', 'string', 'max:255'],
            'stack' => ['nullable', 'string', 'max:20000'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
