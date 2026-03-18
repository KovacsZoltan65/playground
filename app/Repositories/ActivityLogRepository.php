<?php

namespace App\Repositories;

use App\Repositories\Contracts\ActivityLogRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\Paginator;
use Spatie\Activitylog\Models\Activity;

class ActivityLogRepository implements ActivityLogRepositoryInterface
{
    public function paginateForIndex(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $sortField = $filters['sort_field'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        $page = Paginator::resolveCurrentPage('page');
        $appendQuery = $this->buildAppendQuery($filters, $perPage, $page);

        $query = $this->buildIndexQuery($filters);
        $this->applySorting($query, $sortField, $sortDirection);

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $paginator->appends($appendQuery);

        return $paginator;
    }

    public function logNameOptions(): array
    {
        return Activity::query()
            ->select('log_name')
            ->whereNotNull('log_name')
            ->distinct()
            ->orderBy('log_name')
            ->pluck('log_name')
            ->filter()
            ->map(fn (string $logName) => [
                'label' => $logName,
                'value' => $logName,
            ])
            ->values()
            ->all();
    }

    public function eventOptions(): array
    {
        return Activity::query()
            ->select('event')
            ->whereNotNull('event')
            ->distinct()
            ->orderBy('event')
            ->pluck('event')
            ->filter()
            ->map(fn (string $event) => [
                'label' => $event,
                'value' => $event,
            ])
            ->values()
            ->all();
    }

    private function buildIndexQuery(array $filters = []): Builder
    {
        $search = $filters['global'] ?? null;
        $logName = $filters['log_name'] ?? null;
        $event = $filters['event'] ?? null;

        return Activity::query()
            ->with(['causer', 'subject'])
            ->when($search, fn (Builder $query, $value) => $this->applyGlobalSearch($query, (string) $value))
            ->when($logName, fn (Builder $query, $value) => $query->where('log_name', (string) $value))
            ->when($event, fn (Builder $query, $value) => $query->where('event', (string) $value));
    }

    private function applyGlobalSearch(Builder $query, string $search): Builder
    {
        $search = trim($search);

        if ($search === '') {
            return $query;
        }

        return $query->whereLike(
            ['log_name', 'event', 'description', 'subject_type', 'causer_type'],
            $search,
        );
    }

    private function buildAppendQuery(array $filters, int $perPage, int $page): array
    {
        return array_filter([
            'search' => $filters['global'] ?? null,
            'log_name' => $filters['log_name'] ?? null,
            'event' => $filters['event'] ?? null,
            'sort_field' => $filters['sort_field'] ?? 'created_at',
            'sort_direction' => $filters['sort_direction'] ?? 'desc',
            'per_page' => $perPage,
            'page' => $page,
        ], fn ($value) => $value !== null && $value !== '');
    }

    private function applySorting(Builder $query, string $sortField, string $sortDirection): void
    {
        $direction = strtolower($sortDirection) === 'asc' ? 'asc' : 'desc';

        $sortableFields = [
            'description',
            'log_name',
            'event',
            'created_at',
        ];

        $field = in_array($sortField, $sortableFields, true) ? $sortField : 'created_at';

        $query->orderBy($field, $direction)->orderByDesc('id');
    }
}
