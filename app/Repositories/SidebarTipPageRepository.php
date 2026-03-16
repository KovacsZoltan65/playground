<?php

namespace App\Repositories;

use App\Models\SidebarTipPage;
use App\Repositories\Contracts\SidebarTipPageRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SidebarTipPageRepository implements SidebarTipPageRepositoryInterface
{
    public function paginateForIndex(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $search = $filters['global'] ?? null;

        return SidebarTipPage::query()
            ->withCount('tips')
            ->withCount([
                'tips as active_tips_count' => fn ($query) => $query->where('is_active', true),
            ])
            ->when($search, fn ($query, $value) => $query->where('page_component', 'like', "%{$value}%"))
            ->orderBy('page_component')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findWithTips(SidebarTipPage $sidebarTipPage): SidebarTipPage
    {
        return $sidebarTipPage->load(['tips' => fn ($query) => $query->orderBy('sort_order')->orderBy('id')]);
    }

    public function findByPageComponent(string $pageComponent): ?SidebarTipPage
    {
        return SidebarTipPage::query()
            ->where('page_component', $pageComponent)
            ->with(['tips' => fn ($query) => $query
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')])
            ->first();
    }

    public function create(array $attributes, array $tipAttributes): SidebarTipPage
    {
        $sidebarTipPage = SidebarTipPage::query()->create($attributes);

        $this->syncTips($sidebarTipPage, $tipAttributes);

        return $this->findWithTips($sidebarTipPage);
    }

    public function update(SidebarTipPage $sidebarTipPage, array $attributes, array $tipAttributes): SidebarTipPage
    {
        $sidebarTipPage->update($attributes);

        $this->syncTips($sidebarTipPage, $tipAttributes);

        return $this->findWithTips($sidebarTipPage->refresh());
    }

    public function delete(SidebarTipPage $sidebarTipPage): bool
    {
        return (bool) $sidebarTipPage->delete();
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
}
