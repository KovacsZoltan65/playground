<?php

namespace App\Repositories\Contracts;

use App\Models\SidebarTipPage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface SidebarTipPageRepositoryInterface
{
    public function paginateForIndex(array $filters = [], int $perPage = 10, ?bool $needCache = null): LengthAwarePaginator;

    public function findWithTips(SidebarTipPage $sidebarTipPage): SidebarTipPage;

    public function findByPageComponent(string $pageComponent): ?SidebarTipPage;

    public function create(array $attributes, array $tipAttributes): SidebarTipPage;

    public function update(SidebarTipPage $sidebarTipPage, array $attributes, array $tipAttributes): SidebarTipPage;

    public function delete(SidebarTipPage $sidebarTipPage): bool;
}
