<?php

namespace App\Repositories\Contracts;

use App\Models\Employee;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface EmployeeRepositoryInterface
{
    public function paginateForIndex(array $filters = [], int $perPage = 10, ?bool $needCache = null): LengthAwarePaginator;

    public function create(array $attributes): Employee;

    public function update(Employee $employee, array $attributes): Employee;

    public function toggleActiveStatus(Employee $employee): Employee;

    public function delete(Employee $employee): bool;

    public function bulkDeleteByIds(array $ids): int;
}
