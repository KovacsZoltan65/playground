<?php

namespace App\Http\Controllers;

use App\Data\RoleData;
use App\Services\RoleService;
use App\Support\Permissions\PermissionGuards;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function __construct(
        private readonly RoleService $roleService,
    ) {
    }

    public function index(): Response
    {
        $this->authorize('viewAny', Role::class);

        return Inertia::render('Role/Index', [
            'guardOptions' => PermissionGuards::options(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Role::class);

        return Inertia::render('Role/Create', [
            'guardOptions' => PermissionGuards::options(),
            'permissionOptions' => $this->roleService->permissionOptions(PermissionGuards::default()),
        ]);
    }

    public function edit(Role $role): Response
    {
        $this->authorize('update', $role);

        return Inertia::render('Role/Edit', [
            'roleId' => $role->id,
            'guardOptions' => PermissionGuards::options(),
            'permissionOptions' => $this->roleService->permissionOptions($role->guard_name),
        ]);
    }

    public function list(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Role::class);

        return response()->json(
            $this->roleService->listForIndex(
                filters: [
                    'global' => $request->string('search')->toString() ?: null,
                    'name' => $request->string('name')->toString() ?: null,
                    'guard_name' => $request->string('guard_name')->toString() ?: null,
                ],
                perPage: (int) $request->integer('per_page', 10),
            ),
        );
    }

    public function show(Role $role): JsonResponse
    {
        $this->authorize('view', $role);

        return response()->json([
            'data' => $this->roleService->show($role),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Role::class);

        $role = $this->roleService->create(RoleData::fromRequest($request));

        return response()->json([
            'message' => __('Role created successfully.'),
            'data' => $role,
        ], 201);
    }

    public function update(Request $request, Role $role): JsonResponse
    {
        $this->authorize('update', $role);

        $updatedRole = $this->roleService->update($role, RoleData::fromRequest($request, $role));

        return response()->json([
            'message' => __('Role updated successfully.'),
            'data' => $updatedRole,
        ]);
    }

    public function destroy(Role $role): JsonResponse
    {
        $this->authorize('delete', $role);

        $this->roleService->delete($role);

        return response()->json([
            'message' => __('Role deleted successfully.'),
        ]);
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        $this->authorize('deleteAny', Role::class);

        $deletedCount = $this->roleService->bulkDelete(RoleData::validateBulkDeleteIds($request));

        return response()->json([
            'message' => __('Roles deleted successfully.'),
            'deleted' => $deletedCount,
        ]);
    }
}
