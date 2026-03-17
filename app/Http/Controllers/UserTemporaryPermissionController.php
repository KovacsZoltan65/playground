<?php

namespace App\Http\Controllers;

use App\Data\UserTemporaryPermissionData;
use App\Models\UserTemporaryPermission;
use App\Services\UserTemporaryPermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserTemporaryPermissionController extends Controller
{
    public function __construct(
        private readonly UserTemporaryPermissionService $service,
    ) {
    }

    public function index(): Response
    {
        $this->authorize('viewAny', UserTemporaryPermission::class);

        return Inertia::render('UserTemporaryPermission/Index', [
            'userOptions' => $this->service->userOptions(),
            'permissionOptions' => $this->service->permissionOptions(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', UserTemporaryPermission::class);

        return Inertia::render('UserTemporaryPermission/Create', [
            'userOptions' => $this->service->userOptions(),
            'permissionOptions' => $this->service->permissionOptions(),
            'userEffectivePermissionIds' => $this->service->userEffectivePermissionIds(),
        ]);
    }

    public function edit(UserTemporaryPermission $userTemporaryPermission): Response
    {
        $this->authorize('update', $userTemporaryPermission);

        return Inertia::render('UserTemporaryPermission/Edit', [
            'userTemporaryPermissionId' => $userTemporaryPermission->id,
            'userOptions' => $this->service->userOptions(),
            'permissionOptions' => $this->service->permissionOptions(),
        ]);
    }

    public function list(Request $request): JsonResponse
    {
        $this->authorize('viewAny', UserTemporaryPermission::class);

        return response()->json(
            $this->service->listForIndex(
                filters: [
                    'global' => $request->string('search')->toString() ?: null,
                    'user_id' => $request->integer('user_id') ?: null,
                    'permission_id' => $request->integer('permission_id') ?: null,
                    'status' => $request->string('status')->toString() ?: null,
                ],
                perPage: (int) $request->integer('per_page', 10),
            ),
        );
    }

    public function show(UserTemporaryPermission $userTemporaryPermission): JsonResponse
    {
        $this->authorize('view', $userTemporaryPermission);

        return response()->json([
            'data' => $this->service->show($userTemporaryPermission),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', UserTemporaryPermission::class);

        $assignment = $this->service->create(UserTemporaryPermissionData::fromRequest($request));

        return response()->json([
            'message' => __('Temporary permission assigned successfully.'),
            'data' => $assignment,
        ], 201);
    }

    public function update(Request $request, UserTemporaryPermission $userTemporaryPermission): JsonResponse
    {
        $this->authorize('update', $userTemporaryPermission);

        $assignment = $this->service->update(
            $userTemporaryPermission,
            UserTemporaryPermissionData::fromRequest($request, $userTemporaryPermission),
        );

        return response()->json([
            'message' => __('Temporary permission updated successfully.'),
            'data' => $assignment,
        ]);
    }

    public function destroy(UserTemporaryPermission $userTemporaryPermission): JsonResponse
    {
        $this->authorize('delete', $userTemporaryPermission);

        $this->service->delete($userTemporaryPermission);

        return response()->json([
            'message' => __('Temporary permission deleted successfully.'),
        ]);
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        $this->authorize('deleteAny', UserTemporaryPermission::class);

        $deletedCount = $this->service->bulkDelete(UserTemporaryPermissionData::validateBulkDeleteIds($request));

        return response()->json([
            'message' => __('Temporary permissions deleted successfully.'),
            'deleted' => $deletedCount,
        ]);
    }
}
