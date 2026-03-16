<?php

namespace App\Repositories;

use App\Models\Company;
use App\Repositories\Contracts\CompanyRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CompanyRepository implements CompanyRepositoryInterface
{
    public function paginateForIndex(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $search = $filters['global'] ?? null;
        $name = $filters['name'] ?? null;
        $email = $filters['email'] ?? null;
        $phone = $filters['phone'] ?? null;
        $isActive = $filters['is_active'] ?? null;

        return Company::query()
            ->when($search, function ($query, $searchTerm) {
                $query->where(function ($companyQuery) use ($searchTerm) {
                    $companyQuery
                        ->where('name', 'like', "%{$searchTerm}%")
                        ->orWhere('email', 'like', "%{$searchTerm}%")
                        ->orWhere('phone', 'like', "%{$searchTerm}%");
                });
            })
            ->when($name, fn ($query, $value) => $query->where('name', 'like', "%{$value}%"))
            ->when($email, fn ($query, $value) => $query->where('email', 'like', "%{$value}%"))
            ->when($phone, fn ($query, $value) => $query->where('phone', 'like', "%{$value}%"))
            ->when($isActive !== null, function ($query) use ($isActive) {
                $query->where('is_active', $isActive);
            })
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $attributes): Company
    {
        return Company::query()->create($attributes);
    }

    public function update(Company $company, array $attributes): Company
    {
        $company->update($attributes);

        return $company->refresh();
    }

    public function delete(Company $company): bool
    {
        return (bool) $company->delete();
    }

    public function bulkDeleteByIds(array $ids): int
    {
        return Company::query()
            ->whereIn('id', $ids)
            ->delete();
    }
}
