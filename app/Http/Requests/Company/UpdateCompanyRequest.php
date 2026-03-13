<?php

namespace App\Http\Requests\Company;

use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $company = $this->route('company');

        return $company instanceof Company
            ? ($this->user()?->can('update', $company) ?? false)
            : false;
    }

    public function rules(): array
    {
        /** @var Company $company */
        $company = $this->route('company');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('companies', 'name')->ignore($company->id)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('companies', 'email')->ignore($company->id)],
            'phone' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
