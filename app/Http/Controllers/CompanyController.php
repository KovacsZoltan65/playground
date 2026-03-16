<?php

namespace App\Http\Controllers;

use App\Data\CompanyData;
use App\Models\Company;
use App\Services\CompanyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CompanyController extends Controller
{
    public function __construct(
        private readonly CompanyService $companyService,
    ) {
    }

    public function index(): Response
    {
        $this->authorize('viewAny', Company::class);

        return Inertia::render('Company/Index');
    }

    public function create(): Response
    {
        $this->authorize('create', Company::class);

        return Inertia::render('Company/Create');
    }

    public function edit(Company $company): Response
    {
        $this->authorize('update', $company);

        return Inertia::render('Company/Edit', [
            'companyId' => $company->id,
        ]);
    }

    public function list(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Company::class);

        $payload = $this->companyService->listForIndex(
            filters: [
                'global' => $request->string('search')->toString() ?: null,
                'name' => $request->string('name')->toString() ?: null,
                'email' => $request->string('email')->toString() ?: null,
                'phone' => $request->string('phone')->toString() ?: null,
                'is_active' => $this->normalizeBooleanFilter($request->input('is_active')),
            ],
            perPage: (int) $request->integer('per_page', 10),
        );

        return response()->json($payload);
    }

    public function show(Company $company): JsonResponse
    {
        $this->authorize('view', $company);

        return response()->json([
            'data' => $this->companyService->show($company),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Company::class);

        $companyData = CompanyData::fromRequest($request);

        $company = $this->companyService->create($companyData);

        return response()->json([
            'message' => __('Company created successfully.'),
            'data' => $company,
        ], 201);
    }

    public function update(Request $request, Company $company): JsonResponse
    {
        $this->authorize('update', $company);

        $companyData = CompanyData::fromRequest($request, $company);

        $updatedCompany = $this->companyService->update($company, $companyData);

        return response()->json([
            'message' => __('Company updated successfully.'),
            'data' => $updatedCompany,
        ]);
    }

    public function destroy(Company $company): JsonResponse
    {
        $this->authorize('delete', $company);

        $this->companyService->delete($company);

        return response()->json([
            'message' => __('Company deleted successfully.'),
        ]);
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        $this->authorize('deleteAny', Company::class);

        $deletedCount = $this->companyService->bulkDelete(CompanyData::validateBulkDeleteIds($request));

        return response()->json([
            'message' => __('Companies deleted successfully.'),
            'deleted' => $deletedCount,
        ]);
    }

    private function normalizeBooleanFilter(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }
}
