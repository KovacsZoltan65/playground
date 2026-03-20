<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogViewerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function __construct(
        private readonly ActivityLogViewerService $activityLogViewerService,
    ) {
    }

    public function index(): Response
    {
        $this->authorize('viewAny', Activity::class);

        return Inertia::render('ActivityLog/Index', [
            'logNameOptions' => $this->activityLogViewerService->logNameOptions(),
            'eventOptions' => $this->activityLogViewerService->eventOptions(),
        ]);
    }

    public function list(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Activity::class);

        return response()->json(
            $this->activityLogViewerService->listForIndex(
                filters: $this->filtersFromRequest($request, includeSorting: true),
                perPage: (int) $request->integer('per_page', 10),
            ),
        );
    }

    public function analysis(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Activity::class);

        return response()->json(
            $this->activityLogViewerService->analysis(
                filters: $this->filtersFromRequest($request),
            ),
        );
    }

    private function filtersFromRequest(Request $request, bool $includeSorting = false): array
    {
        $filters = [
            'global' => $request->string('search')->toString() ?: null,
            'log_name' => $request->string('log_name')->toString() ?: null,
            'event' => $request->string('event')->toString() ?: null,
        ];

        if ($includeSorting) {
            $filters['sort_field'] = $request->string('sort_field')->toString() ?: 'created_at';
            $filters['sort_direction'] = $request->string('sort_direction')->toString() ?: 'desc';
        }

        return $filters;
    }
}
