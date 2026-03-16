<?php

namespace App\Repositories\Contracts;

use App\Models\Company;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CompanyRepositoryInterface
{
    public function paginateForIndex(array $filters = [], int $perPage = 10): LengthAwarePaginator;

    public function create(array $attributes): Company;

    public function update(Company $company, array $attributes): Company;

    public function toggleActiveStatus(Company $company): Company;

    public function delete(Company $company): bool;

    public function bulkDeleteByIds(array $ids): int;
}
