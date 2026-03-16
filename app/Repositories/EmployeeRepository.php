<?php

namespace App\Repositories;

use App\Models\Employee;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EmployeeRepository implements EmployeeRepositoryInterface
{
    public function paginateForIndex(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $search = $filters['global'] ?? null;
        $companyId = $filters['company_id'] ?? null;
        $name = $filters['name'] ?? null;
        $email = $filters['email'] ?? null;
        $active = $filters['active'] ?? null;
        $sortField = $filters['sort_field'] ?? 'name';
        $sortDirection = $filters['sort_direction'] ?? 'asc';

        $query = Employee::query()
            ->with('company:id,name')
            ->when($search, function ($query, $searchTerm) {
                $query->where(function ($employeeQuery) use ($searchTerm) {
                    $employeeQuery
                        ->where('name', 'like', "%{$searchTerm}%")
                        ->orWhere('email', 'like', "%{$searchTerm}%")
                        ->orWhereHas('company', fn ($companyQuery) => $companyQuery->where('name', 'like', "%{$searchTerm}%"));
                });
            })
            ->when($companyId, fn ($query, $value) => $query->where('company_id', $value))
            ->when($name, fn ($query, $value) => $query->where('name', 'like', "%{$value}%"))
            ->when($email, fn ($query, $value) => $query->where('email', 'like', "%{$value}%"))
            ->when($active !== null, fn ($query) => $query->where('active', $active));

        $this->applySorting($query, $sortField, $sortDirection);

        return $query->paginate($perPage)->withQueryString();
    }

    public function create(array $attributes): Employee
    {
        return Employee::query()->create($attributes);
    }

    public function update(Employee $employee, array $attributes): Employee
    {
        $employee->update($attributes);

        return $employee->refresh()->load('company:id,name');
    }

    public function toggleActiveStatus(Employee $employee): Employee
    {
        $employee->update([
            'active' => ! $employee->active,
        ]);

        return $employee->refresh()->load('company:id,name');
    }

    public function delete(Employee $employee): bool
    {
        return (bool) $employee->delete();
    }

    public function bulkDeleteByIds(array $ids): int
    {
        return Employee::query()
            ->whereIn('id', $ids)
            ->delete();
    }

    private function applySorting($query, string $sortField, string $sortDirection): void
    {
        $direction = strtolower($sortDirection) === 'desc' ? 'desc' : 'asc';

        if ($sortField === 'company_name') {
            $query->join('companies', 'companies.id', '=', 'employees.company_id')
                ->orderBy('companies.name', $direction)
                ->select('employees.*');

            return;
        }

        $sortableFields = [
            'name',
            'email',
            'active',
            'updated_at',
            'created_at',
        ];

        $field = in_array($sortField, $sortableFields, true) ? $sortField : 'name';

        $query->orderBy($field, $direction);
    }
}
