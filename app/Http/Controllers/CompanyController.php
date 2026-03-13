<?php

namespace App\Http\Controllers;

use App\Data\CompanyData;
use App\Http\Requests\Company\StoreCompanyRequest;
use App\Http\Requests\Company\UpdateCompanyRequest;
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
            search: $request->string('search')->toString() ?: null,
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

    public function store(StoreCompanyRequest $request): JsonResponse
    {
        $companyData = CompanyData::from([
            'id' => null,
            'name' => $request->string('name')->toString(),
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),
            'address' => $request->input('address'),
            'is_active' => $request->boolean('is_active'),
        ]);

        $company = $this->companyService->create($companyData);

        return response()->json([
            'message' => __('Company created successfully.'),
            'data' => $company,
        ], 201);
    }

    public function update(UpdateCompanyRequest $request, Company $company): JsonResponse
    {
        $companyData = CompanyData::from([
            'id' => $company->id,
            'name' => $request->string('name')->toString(),
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),
            'address' => $request->input('address'),
            'is_active' => $request->boolean('is_active'),
        ]);

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
}
