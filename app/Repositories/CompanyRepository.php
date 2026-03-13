<?php

namespace App\Repositories;

use App\Models\Company;
use App\Repositories\Contracts\CompanyRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CompanyRepository implements CompanyRepositoryInterface
{
    public function paginateForIndex(?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        return Company::query()
            ->when($search, function ($query, $searchTerm) {
                $query->where(function ($companyQuery) use ($searchTerm) {
                    $companyQuery
                        ->where('name', 'like', "%{$searchTerm}%")
                        ->orWhere('email', 'like', "%{$searchTerm}%")
                        ->orWhere('phone', 'like', "%{$searchTerm}%");
                });
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
}
