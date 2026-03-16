<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFrontendErrorRequest;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;

class FrontendErrorController extends Controller
{
    public function __construct(
        private readonly ActivityLogService $activityLogService,
    ) {
    }

    public function store(StoreFrontendErrorRequest $request): JsonResponse
    {
        $payload = $request->validated();

        if ($request->user()) {
            $payload['user_id'] = $request->user()->id;
        }

        $this->activityLogService->logFrontendError($payload);

        return response()->json([
            'status' => 'ok',
        ], 201);
    }
}
