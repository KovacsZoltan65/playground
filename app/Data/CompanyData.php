<?php

namespace App\Data;

use App\Models\Company;
use App\Support\Validation\SharedLaravelValidationRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/**
 * A cégkezelő oldalak és service-ek közötti adattranszferért felelős DTO.
 */
class CompanyData extends Data
{
    public function __construct(
        public ?int $id,
        public string $name,
        public ?string $email,
        public ?string $phone,
        public ?string $address,
        public bool $is_active,
        public int $employees_count = 0,
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
            employees_count: (int) ($company->employees_count ?? 0),
            created_at: $company->created_at?->toDateTimeString(),
            updated_at: $company->updated_at?->toDateTimeString(),
        );
    }

    public static function fromRequest(Request $request, ?Company $company = null): self
    {
        return self::validateAndCreate([
            'id' => $company?->id,
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),
            'address' => $request->input('address'),
            'is_active' => $request->boolean('is_active'),
        ]);
    }

    /**
     * @return array<int, int>
     */
    public static function validateBulkDeleteIds(Request $request): array
    {
        return Validator::make($request->all(), [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct', 'exists:companies,id'],
        ])->validate()['ids'];
    }

    public static function rules(?ValidationContext $context = null): array
    {
        /** @var Company|null $company */
        $company = request()->route('company');

        return SharedLaravelValidationRules::for('company', [
            'company' => $company,
        ]);
    }

    /**
     * @return array{name:string,email:?string,phone:?string,address:?string,is_active:bool}
     */
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
