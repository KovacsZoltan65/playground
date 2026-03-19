<?php

namespace App\Services;

use App\Data\SidebarTipPageData;
use App\Models\SidebarTipPage;
use App\Repositories\Contracts\SidebarTipPageRepositoryInterface;
use App\Support\SidebarTips\SidebarTipTargets;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SidebarTipPageService
{
    public function __construct(
        private readonly SidebarTipPageRepositoryInterface $sidebarTipPages,
    ) {
    }

    public function listForIndex(array $filters = [], int $perPage = 10): array
    {
        /** @var LengthAwarePaginator<int, SidebarTipPage> $paginator */
        $paginator = $this->sidebarTipPages->paginateForIndex($filters, $perPage);

        return [
            'data' => $paginator->getCollection()
                ->map(fn (SidebarTipPage $sidebarTipPage) => SidebarTipPageData::fromModel($sidebarTipPage))
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

    public function show(SidebarTipPage $sidebarTipPage): SidebarTipPageData
    {
        return SidebarTipPageData::fromModel(
            $this->sidebarTipPages->findWithTips($sidebarTipPage),
        );
    }

    public function create(SidebarTipPageData $sidebarTipPageData): SidebarTipPageData
    {
        $sidebarTipPage = DB::transaction(fn () => $this->sidebarTipPages->create(
            $sidebarTipPageData->toRepositoryAttributes(),
            $sidebarTipPageData->tipAttributes(),
        ));

        return SidebarTipPageData::fromModel($sidebarTipPage);
    }

    public function update(SidebarTipPage $sidebarTipPage, SidebarTipPageData $sidebarTipPageData): SidebarTipPageData
    {
        $updatedSidebarTipPage = DB::transaction(fn () => $this->sidebarTipPages->update(
            $sidebarTipPage,
            $sidebarTipPageData->toRepositoryAttributes(),
            $sidebarTipPageData->tipAttributes(),
        ));

        return SidebarTipPageData::fromModel($updatedSidebarTipPage);
    }

    public function delete(SidebarTipPage $sidebarTipPage): bool
    {
        return $this->sidebarTipPages->delete($sidebarTipPage);
    }

    public function resolveForRoute(?string $routeName): array
    {
        $pageComponent = SidebarTipTargets::componentForRouteName($routeName);

        if ($pageComponent === null) {
            return $this->defaultSidebarConfig();
        }

        $sidebarTipPage = $this->sidebarTipPages->findByPageComponent($pageComponent);

        if ($sidebarTipPage === null) {
            return $this->defaultSidebarConfig();
        }

        return [
            'visible' => $sidebarTipPage->is_visible,
            'rotationIntervalMs' => $sidebarTipPage->rotation_interval_seconds * 1000,
            'tips' => $sidebarTipPage->tips
                ->pluck('content')
                ->values()
                ->all(),
        ];
    }

    private function defaultSidebarConfig(): array
    {
        return [
            'visible' => true,
            'rotationIntervalMs' => 60 * 1000,
            'tips' => [],
        ];
    }
}
