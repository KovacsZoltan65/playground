<?php

namespace App\Repositories;

use App\Models\SidebarTipPage;
use App\Repositories\Contracts\SidebarTipPageRepositoryInterface;
use App\Services\CacheService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\Paginator;

class SidebarTipPageRepository implements SidebarTipPageRepositoryInterface
{
    public function __construct(
        private readonly CacheService $cacheService,
    ) {
    }

    public function paginateForIndex(array $filters = [], int $perPage = 10, ?bool $needCache = null): LengthAwarePaginator
    {
        $needCache ??= (bool) config('cache.enable_sidebar_tip_pages', false);
        $page = Paginator::resolveCurrentPage('page');
        $appendQuery = $this->buildAppendQuery($filters, $perPage, $page);

        $queryCallback = function () use ($filters, $perPage, $page, $appendQuery): LengthAwarePaginator {
            $paginator = $this->buildIndexQuery($filters)
                ->orderBy('page_component')
                ->paginate($perPage, ['*'], 'page', $page);

            $paginator->appends($appendQuery);

            return $paginator;
        };

        if (! $needCache) {
            return $queryCallback();
        }

        return $this->cacheService->remember(
            $this->sidebarTipPagesCacheTag(),
            $this->buildPaginateCacheKey($filters, $perPage, $page),
            $queryCallback,
            $this->fetchCacheTtlInSeconds(),
        );
    }

    public function findWithTips(SidebarTipPage $sidebarTipPage): SidebarTipPage
    {
        return $sidebarTipPage->load(['tips' => fn ($query) => $query->orderBy('sort_order')->orderBy('id')]);
    }

    public function findByPageComponent(string $pageComponent): ?SidebarTipPage
    {
        return $this->cacheService->remember(
            $this->sidebarTipPagesCacheTag(),
            $this->buildPageComponentCacheKey($pageComponent),
            fn () => SidebarTipPage::query()
                ->where('page_component', $pageComponent)
                ->with(['tips' => fn ($query) => $query
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('id')])
                ->first(),
            $this->fetchCacheTtlInSeconds(),
        );
    }

    public function create(array $attributes, array $tipAttributes): SidebarTipPage
    {
        $sidebarTipPage = SidebarTipPage::query()->create($attributes);

        $this->syncTips($sidebarTipPage, $tipAttributes);
        $this->flushCache();

        return $this->findWithTips($sidebarTipPage);
    }

    public function update(SidebarTipPage $sidebarTipPage, array $attributes, array $tipAttributes): SidebarTipPage
    {
        $sidebarTipPage->update($attributes);

        $this->syncTips($sidebarTipPage, $tipAttributes);
        $this->flushCache();

        return $this->findWithTips($sidebarTipPage->refresh());
    }

    public function delete(SidebarTipPage $sidebarTipPage): bool
    {
        $deleted = (bool) $sidebarTipPage->delete();

        if ($deleted) {
            $this->flushCache();
        }

        return $deleted;
    }

    private function syncTips(SidebarTipPage $sidebarTipPage, array $tipAttributes): void
    {
        $existingIds = $sidebarTipPage->tips()->pluck('id')->all();
        $incomingIds = collect($tipAttributes)
            ->pluck('id')
            ->filter()
            ->all();

        $idsToDelete = array_diff($existingIds, $incomingIds);

        if ($idsToDelete !== []) {
            $sidebarTipPage->tips()->whereIn('id', $idsToDelete)->delete();
        }

        foreach ($tipAttributes as $attributes) {
            $tipId = $attributes['id'] ?? null;

            $payload = [
                'content' => $attributes['content'],
                'sort_order' => $attributes['sort_order'],
                'is_active' => $attributes['is_active'],
            ];

            if ($tipId) {
                $sidebarTipPage->tips()->whereKey($tipId)->update($payload);
                continue;
            }

            $sidebarTipPage->tips()->create($payload);
        }
    }

    private function buildIndexQuery(array $filters = []): Builder
    {
        $search = $filters['global'] ?? null;

        return SidebarTipPage::query()
            ->withCount('tips')
            ->withCount([
                'tips as active_tips_count' => fn ($query) => $query->where('is_active', true),
            ])
            ->when($search, fn ($query, $value) => $query->where('page_component', 'like', "%{$value}%"));
    }

    private function buildAppendQuery(array $filters, int $perPage, int $page): array
    {
        return array_filter([
            'search' => $filters['global'] ?? null,
            'per_page' => $perPage,
            'page' => $page,
        ], fn ($value) => $value !== null && $value !== '');
    }

    private function buildPaginateCacheKey(array $filters, int $perPage, int $page): string
    {
        $payload = json_encode([
            'filters' => [
                'global' => $filters['global'] ?? null,
            ],
            'per_page' => $perPage,
            'page' => $page,
        ]);

        return 'sidebar_tip_pages.paginate.'.sha1((string) $payload);
    }

    private function buildPageComponentCacheKey(string $pageComponent): string
    {
        return 'sidebar_tip_pages.component.'.sha1($pageComponent);
    }

    private function fetchCacheTtlInSeconds(): int
    {
        return max((int) config('cache.sidebar_tip_pages_ttl', 300), 1);
    }

    private function flushCache(): void
    {
        $this->cacheService->forgetAll($this->sidebarTipPagesCacheTag());
    }

    private function sidebarTipPagesCacheTag(): string
    {
        return SidebarTipPage::getTag();
    }
}
