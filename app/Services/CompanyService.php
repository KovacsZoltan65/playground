<?php

namespace App\Services;

use App\Data\CompanyData;
use App\Models\Company;
use App\Repositories\Contracts\CompanyRepositoryInterface;

class CompanyService
{
    public function __construct(
        private readonly CompanyRepositoryInterface $companies,
    ) {
    }

    public function listForIndex(?string $search = null, int $perPage = 10): array
    {
        $paginator = $this->companies->paginateForIndex($search, $perPage);

        return [
            'data' => $paginator->getCollection()
                ->map(fn (Company $company) => CompanyData::fromModel($company))
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

    public function show(Company $company): CompanyData
    {
        return CompanyData::fromModel($company);
    }

    public function create(CompanyData $companyData): CompanyData
    {
        $company = $this->companies->create($companyData->toRepositoryAttributes());

        return CompanyData::fromModel($company);
    }

    public function update(Company $company, CompanyData $companyData): CompanyData
    {
        $updatedCompany = $this->companies->update($company, $companyData->toRepositoryAttributes());

        return CompanyData::fromModel($updatedCompany);
    }

    public function delete(Company $company): bool
    {
        return $this->companies->delete($company);
    }
}
