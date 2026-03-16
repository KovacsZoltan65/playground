<?php

namespace App\Http\Controllers;

use App\Data\SidebarTipPageData;
use App\Models\SidebarTipPage;
use App\Services\SidebarTipPageService;
use App\Support\SidebarTips\SidebarTipTargets;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SidebarTipPageController extends Controller
{
    public function __construct(
        private readonly SidebarTipPageService $sidebarTipPageService,
    ) {
    }

    public function index(): Response
    {
        $this->authorize('viewAny', SidebarTipPage::class);

        return Inertia::render('UsageTips/Index');
    }

    public function create(): Response
    {
        $this->authorize('create', SidebarTipPage::class);

        return Inertia::render('UsageTips/Create', [
            'pageTargets' => SidebarTipTargets::options(),
        ]);
    }

    public function edit(SidebarTipPage $sidebarTipPage): Response
    {
        $this->authorize('update', $sidebarTipPage);

        return Inertia::render('UsageTips/Edit', [
            'sidebarTipPageId' => $sidebarTipPage->id,
            'pageTargets' => SidebarTipTargets::options(),
        ]);
    }

    public function list(Request $request): JsonResponse
    {
        $this->authorize('viewAny', SidebarTipPage::class);

        return response()->json(
            $this->sidebarTipPageService->listForIndex(
                filters: [
                    'global' => $request->string('search')->toString() ?: null,
                ],
                perPage: (int) $request->integer('per_page', 10),
            )
        );
    }

    public function show(SidebarTipPage $sidebarTipPage): JsonResponse
    {
        $this->authorize('view', $sidebarTipPage);

        return response()->json([
            'data' => $this->sidebarTipPageService->show($sidebarTipPage),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', SidebarTipPage::class);

        $sidebarTipPage = $this->sidebarTipPageService->create(
            SidebarTipPageData::fromRequest($request),
        );

        return response()->json([
            'message' => __('Usage tips created successfully.'),
            'data' => $sidebarTipPage,
        ], 201);
    }

    public function update(Request $request, SidebarTipPage $sidebarTipPage): JsonResponse
    {
        $this->authorize('update', $sidebarTipPage);

        $updatedSidebarTipPage = $this->sidebarTipPageService->update(
            $sidebarTipPage,
            SidebarTipPageData::fromRequest($request, $sidebarTipPage),
        );

        return response()->json([
            'message' => __('Usage tips updated successfully.'),
            'data' => $updatedSidebarTipPage,
        ]);
    }

    public function destroy(SidebarTipPage $sidebarTipPage): JsonResponse
    {
        $this->authorize('delete', $sidebarTipPage);

        $this->sidebarTipPageService->delete($sidebarTipPage);

        return response()->json([
            'message' => __('Usage tips deleted successfully.'),
        ]);
    }
}
