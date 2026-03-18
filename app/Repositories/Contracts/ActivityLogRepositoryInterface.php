<?php

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ActivityLogRepositoryInterface
{
    public function paginateForIndex(array $filters = [], int $perPage = 10): LengthAwarePaginator;

    public function logNameOptions(): array;

    public function eventOptions(): array;
}
