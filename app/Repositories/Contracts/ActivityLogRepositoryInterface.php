<?php

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ActivityLogRepositoryInterface
{
    public function paginateForIndex(array $filters = [], int $perPage = 10): LengthAwarePaginator;

    public function summarizeForAnalysis(array $filters = []): array;

    public function logNameOptions(): array;

    public function eventOptions(): array;
}
