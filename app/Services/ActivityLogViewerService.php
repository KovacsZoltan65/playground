<?php

namespace App\Services;

use App\Data\ActivityLogData;
use App\Repositories\Contracts\ActivityLogRepositoryInterface;
use Spatie\Activitylog\Models\Activity;

class ActivityLogViewerService
{
    public function __construct(
        private readonly ActivityLogRepositoryInterface $activityLogs,
    ) {
    }

    public function listForIndex(array $filters = [], int $perPage = 10): array
    {
        $paginator = $this->activityLogs->paginateForIndex($filters, $perPage);

        return [
            'data' => collect($paginator->items())
                ->map(fn (Activity $activity) => ActivityLogData::fromModel($activity))
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

    public function analysis(array $filters = []): array
    {
        return $this->activityLogs->summarizeForAnalysis($filters);
    }

    public function logNameOptions(): array
    {
        return $this->activityLogs->logNameOptions();
    }

    public function eventOptions(): array
    {
        return $this->activityLogs->eventOptions();
    }
}
