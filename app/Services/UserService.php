<?php

namespace App\Services;

use App\Data\UserData;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;

class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {
    }

    public function listForIndex(array $filters = [], int $perPage = 10): array
    {
        $paginator = $this->users->paginateForIndex($filters, $perPage);

        return [
            'data' => $paginator->getCollection()
                ->map(fn (User $user) => UserData::fromModel($user))
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

    public function show(User $user): UserData
    {
        return UserData::fromModel($user);
    }

    public function create(UserData $userData): UserData
    {
        $user = DB::transaction(function () use ($userData): User {
            $user = $this->users->create($userData->toRepositoryAttributes());

            return $this->users->syncRoles($user, $userData->roleIds());
        });

        $user->sendEmailVerificationNotification();

        return UserData::fromModel($user);
    }

    public function update(User $user, UserData $userData): UserData
    {
        $updatedUser = DB::transaction(function () use ($user, $userData): User {
            $updatedUser = $this->users->update($user, $userData->toRepositoryAttributes());

            return $this->users->syncRoles($updatedUser, $userData->roleIds());
        });

        return UserData::fromModel($updatedUser);
    }

    public function delete(User $user): bool
    {
        return DB::transaction(fn (): bool => $this->users->delete($user));
    }

    public function bulkDelete(array $ids): int
    {
        return DB::transaction(fn (): int => $this->users->bulkDeleteByIds($ids));
    }

    public function roleOptions(): array
    {
        return $this->users->roleOptions();
    }

    public function sendVerificationEmail(User $user): void
    {
        $user->sendEmailVerificationNotification();
    }
}
