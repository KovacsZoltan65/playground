<?php

namespace App\Services;

use App\Data\EmployeeData;
use App\Models\Employee;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class EmployeeService
{
    public function __construct(
        private readonly EmployeeRepositoryInterface $employees,
        private readonly CompanyService $companyService,
    ) {
    }

    public function listForIndex(array $filters = [], int $perPage = 10): array
    {
        /** @var LengthAwarePaginator<int, Employee> $paginator */
        $paginator = $this->employees->paginateForIndex($filters, $perPage);

        return [
            'data' => $paginator->getCollection()
                ->map(fn (Employee $employee) => EmployeeData::fromModel($employee))
                ->values()
                ->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }

    public function show(Employee $employee): EmployeeData
    {
        return EmployeeData::fromModel($employee);
    }

    public function create(EmployeeData $employeeData): EmployeeData
    {
        $employee = $this->employees->create($employeeData->toRepositoryAttributes());

        return EmployeeData::fromModel($employee);
    }

    public function update(Employee $employee, EmployeeData $employeeData): EmployeeData
    {
        $updatedEmployee = $this->employees->update($employee, $employeeData->toRepositoryAttributes());

        return EmployeeData::fromModel($updatedEmployee);
    }

    public function toggleActiveStatus(Employee $employee): EmployeeData
    {
        $updatedEmployee = $this->employees->toggleActiveStatus($employee);

        return EmployeeData::fromModel($updatedEmployee);
    }

    public function delete(Employee $employee): bool
    {
        return $this->employees->delete($employee);
    }

    public function bulkDelete(array $ids): int
    {
        return $this->employees->bulkDeleteByIds($ids);
    }

    public function companyOptions(): array
    {
        return $this->companyService->optionsForSelect();
    }
}
