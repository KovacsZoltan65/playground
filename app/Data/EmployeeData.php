<?php

namespace App\Data;

use App\Models\Employee;
use App\Support\Validation\SharedLaravelValidationRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/**
 * Az alkalmazotti űrlapok és listaoldalak számára egységes employee DTO.
 */
class EmployeeData extends Data
{
    public function __construct(
        public ?int $id,
        public int $company_id,
        public string $name,
        public string $email,
        public bool $active,
        public ?string $company_name = null,
        public ?string $created_at = null,
        public ?string $updated_at = null,
        public ?string $deleted_at = null,
    ) {
    }

    public static function fromModel(Employee $employee): self
    {
        $employee->loadMissing('company:id,name');

        return new self(
            id: $employee->id,
            company_id: $employee->company_id,
            name: $employee->name,
            email: $employee->email,
            active: $employee->active,
            company_name: $employee->company?->name,
            created_at: $employee->created_at?->toDateTimeString(),
            updated_at: $employee->updated_at?->toDateTimeString(),
            deleted_at: $employee->deleted_at?->toDateTimeString(),
        );
    }

    public static function fromRequest(Request $request, ?Employee $employee = null): self
    {
        return self::validateAndCreate([
            'id' => $employee?->id,
            'company_id' => (int) $request->input('company_id'),
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'active' => $request->boolean('active'),
        ]);
    }

    /**
     * @return array<int, int>
     */
    public static function validateBulkDeleteIds(Request $request): array
    {
        return Validator::make($request->all(), [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct', 'exists:employees,id'],
        ])->validate()['ids'];
    }

    public static function rules(?ValidationContext $context = null): array
    {
        return SharedLaravelValidationRules::for('employee');
    }

    /**
     * @return array{company_id:int,name:string,email:string,active:bool}
     */
    public function toRepositoryAttributes(): array
    {
        return [
            'company_id' => $this->company_id,
            'name' => $this->name,
            'email' => $this->email,
            'active' => $this->active,
        ];
    }
}
