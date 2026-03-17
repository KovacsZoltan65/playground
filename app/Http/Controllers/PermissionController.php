<?php

namespace App\Http\Controllers;

use App\Data\PermissionData;
use App\Services\PermissionService;
use App\Support\Permissions\PermissionGuards;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function __construct(
        private readonly PermissionService $permissionService,
    ) {
    }

    public function index(): Response
    {
        $this->authorize('viewAny', Permission::class);

        return Inertia::render('Permission/Index', [
            'guardOptions' => PermissionGuards::options(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Permission::class);

        return Inertia::render('Permission/Create', [
            'guardOptions' => PermissionGuards::options(),
        ]);
    }

    public function edit(Permission $permission): Response
    {
        $this->authorize('update', $permission);

        return Inertia::render('Permission/Edit', [
            'permissionId' => $permission->id,
            'guardOptions' => PermissionGuards::options(),
        ]);
    }

    public function list(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Permission::class);

        return response()->json(
            $this->permissionService->listForIndex(
                filters: [
                    'global' => $request->string('search')->toString() ?: null,
                    'name' => $request->string('name')->toString() ?: null,
                    'guard_name' => $request->string('guard_name')->toString() ?: null,
                ],
                perPage: (int) $request->integer('per_page', 10),
            ),
        );
    }

    public function show(Permission $permission): JsonResponse
    {
        $this->authorize('view', $permission);

        return response()->json([
            'data' => $this->permissionService->show($permission),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Permission::class);

        $permission = $this->permissionService->create(PermissionData::fromRequest($request));

        return response()->json([
            'message' => __('Permission created successfully.'),
            'data' => $permission,
        ], 201);
    }

    public function update(Request $request, Permission $permission): JsonResponse
    {
        $this->authorize('update', $permission);

        $updatedPermission = $this->permissionService->update($permission, PermissionData::fromRequest($request, $permission));

        return response()->json([
            'message' => __('Permission updated successfully.'),
            'data' => $updatedPermission,
        ]);
    }

    public function destroy(Permission $permission): JsonResponse
    {
        $this->authorize('delete', $permission);

        $this->permissionService->delete($permission);

        return response()->json([
            'message' => __('Permission deleted successfully.'),
        ]);
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        $this->authorize('deleteAny', Permission::class);

        $deletedCount = $this->permissionService->bulkDelete(PermissionData::validateBulkDeleteIds($request));

        return response()->json([
            'message' => __('Permissions deleted successfully.'),
            'deleted' => $deletedCount,
        ]);
    }
}
