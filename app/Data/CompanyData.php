<?php

namespace App\Data;

use App\Models\Company;
use Spatie\LaravelData\Data;

class CompanyData extends Data
{
    public function __construct(
        public ?int $id,
        public string $name,
        public ?string $email,
        public ?string $phone,
        public ?string $address,
        public bool $is_active,
        public ?string $created_at = null,
        public ?string $updated_at = null,
    ) {
    }

    public static function fromModel(Company $company): self
    {
        return new self(
            id: $company->id,
            name: $company->name,
            email: $company->email,
            phone: $company->phone,
            address: $company->address,
            is_active: $company->is_active,
            created_at: $company->created_at?->toDateTimeString(),
            updated_at: $company->updated_at?->toDateTimeString(),
        );
    }

    public function toRepositoryAttributes(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'is_active' => $this->is_active,
        ];
    }
}
