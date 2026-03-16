<?php

namespace App\Http\Controllers;

use App\Data\EmployeeData;
use App\Models\Employee;
use App\Services\EmployeeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    public function __construct(
        private readonly EmployeeService $employeeService,
    ) {
    }

    public function index(): Response
    {
        $this->authorize('viewAny', Employee::class);

        return Inertia::render('Employee/Index', [
            'companyOptions' => $this->employeeService->companyOptions(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Employee::class);

        return Inertia::render('Employee/Create', [
            'companyOptions' => $this->employeeService->companyOptions(),
        ]);
    }

    public function edit(Employee $employee): Response
    {
        $this->authorize('update', $employee);

        return Inertia::render('Employee/Edit', [
            'employeeId' => $employee->id,
            'companyOptions' => $this->employeeService->companyOptions(),
        ]);
    }

    public function list(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Employee::class);

        $payload = $this->employeeService->listForIndex(
            filters: [
                'global' => $request->string('search')->toString() ?: null,
                'company_id' => $request->integer('company_id') ?: null,
                'name' => $request->string('name')->toString() ?: null,
                'email' => $request->string('email')->toString() ?: null,
                'active' => $this->normalizeBooleanFilter($request->input('active')),
                'sort_field' => $request->string('sort_field')->toString() ?: 'name',
                'sort_direction' => $request->string('sort_direction')->toString() ?: 'asc',
            ],
            perPage: (int) $request->integer('per_page', 10),
        );

        return response()->json($payload);
    }

    public function show(Employee $employee): JsonResponse
    {
        $this->authorize('view', $employee);

        return response()->json([
            'data' => $this->employeeService->show($employee),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Employee::class);

        $employeeData = EmployeeData::fromRequest($request);

        $employee = $this->employeeService->create($employeeData);

        return response()->json([
            'message' => __('Employee created successfully.'),
            'data' => $employee,
        ], 201);
    }

    public function update(Request $request, Employee $employee): JsonResponse
    {
        $this->authorize('update', $employee);

        $employeeData = EmployeeData::fromRequest($request, $employee);

        $updatedEmployee = $this->employeeService->update($employee, $employeeData);

        return response()->json([
            'message' => __('Employee updated successfully.'),
            'data' => $updatedEmployee,
        ]);
    }

    public function destroy(Employee $employee): JsonResponse
    {
        $this->authorize('delete', $employee);

        $this->employeeService->delete($employee);

        return response()->json([
            'message' => __('Employee deleted successfully.'),
        ]);
    }

    public function toggleActiveStatus(Employee $employee): JsonResponse
    {
        $this->authorize('update', $employee);

        $updatedEmployee = $this->employeeService->toggleActiveStatus($employee);

        return response()->json([
            'message' => __('Employee status updated successfully.'),
            'data' => $updatedEmployee,
        ]);
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        $this->authorize('deleteAny', Employee::class);

        $deletedCount = $this->employeeService->bulkDelete(EmployeeData::validateBulkDeleteIds($request));

        return response()->json([
            'message' => __('Employees deleted successfully.'),
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
