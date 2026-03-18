<?php

namespace App\Http\Controllers;

use App\Data\UserData;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
    ) {
    }

    public function index(): Response
    {
        $this->authorize('viewAny', User::class);

        return Inertia::render('User/Index', [
            'roleOptions' => $this->userService->roleOptions(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', User::class);

        return Inertia::render('User/Create', [
            'roleOptions' => $this->userService->roleOptions(),
        ]);
    }

    public function edit(User $user): Response
    {
        $this->authorize('update', $user);

        return Inertia::render('User/Edit', [
            'userId' => $user->id,
            'roleOptions' => $this->userService->roleOptions(),
        ]);
    }

    public function list(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        return response()->json(
            $this->userService->listForIndex(
                filters: [
                    'global' => $request->string('search')->toString() ?: null,
                    'name' => $request->string('name')->toString() ?: null,
                    'email' => $request->string('email')->toString() ?: null,
                    'role_id' => $request->integer('role_id') ?: null,
                    'sort_field' => $request->string('sort_field')->toString() ?: 'name',
                    'sort_direction' => $request->string('sort_direction')->toString() ?: 'asc',
                ],
                perPage: (int) $request->integer('per_page', 10),
            ),
        );
    }

    public function show(User $user): JsonResponse
    {
        $this->authorize('view', $user);

        return response()->json([
            'data' => $this->userService->show($user),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', User::class);

        $user = $this->userService->create(UserData::fromRequest($request));

        return response()->json([
            'message' => __('User created successfully.'),
            'data' => $user,
        ], 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);

        $updatedUser = $this->userService->update($user, UserData::fromRequest($request, $user));

        return response()->json([
            'message' => __('User updated successfully.'),
            'data' => $updatedUser,
        ]);
    }

    public function destroy(User $user): JsonResponse
    {
        $this->authorize('delete', $user);

        $this->userService->delete($user);

        return response()->json([
            'message' => __('User deleted successfully.'),
        ]);
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        $this->authorize('deleteAny', User::class);

        $deletedCount = $this->userService->bulkDelete(UserData::validateBulkDeleteIds($request));

        return response()->json([
            'message' => __('Users deleted successfully.'),
            'deleted' => $deletedCount,
        ]);
    }

    public function sendVerificationEmail(User $user): JsonResponse
    {
        $this->authorize('update', $user);

        $alreadyVerified = $user->hasVerifiedEmail();

        $this->userService->sendVerificationEmail($user);

        return response()->json([
            'message' => $alreadyVerified
                ? __('Verification email resent successfully.')
                : __('Verification email sent successfully.'),
        ]);
    }
}
