<?php

namespace App\Data;

use App\Models\SidebarTipPage;
use App\Support\SidebarTips\SidebarTipTargets;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class SidebarTipPageData extends Data
{
    /**
     * @param  array<int, array{id:int|null,content:string,sort_order:int,is_active:bool}>  $tips
     */
    public function __construct(
        public ?int $id,
        public string $page_component,
        public bool $is_visible,
        public int $rotation_interval_seconds,
        public array $tips = [],
        public ?string $page_label_key = null,
        public ?int $tips_count = null,
        public ?int $active_tips_count = null,
    ) {
    }

    public static function fromModel(SidebarTipPage $sidebarTipPage): self
    {
        $sidebarTipPage->loadMissing('tips');

        return new self(
            id: $sidebarTipPage->id,
            page_component: $sidebarTipPage->page_component,
            is_visible: $sidebarTipPage->is_visible,
            rotation_interval_seconds: $sidebarTipPage->rotation_interval_seconds,
            tips: $sidebarTipPage->tips
                ->map(fn ($tip) => [
                    'id' => $tip->id,
                    'content' => $tip->content,
                    'sort_order' => $tip->sort_order,
                    'is_active' => $tip->is_active,
                ])
                ->values()
                ->all(),
            page_label_key: SidebarTipTargets::labelKeyForComponent($sidebarTipPage->page_component),
            tips_count: $sidebarTipPage->tips_count ?? $sidebarTipPage->tips->count(),
            active_tips_count: $sidebarTipPage->active_tips_count
                ?? $sidebarTipPage->tips->where('is_active', true)->count(),
        );
    }

    public static function fromRequest(Request $request, ?SidebarTipPage $sidebarTipPage = null): self
    {
        return self::validateAndCreate([
            'id' => $sidebarTipPage?->id,
            'page_component' => $request->input('page_component'),
            'is_visible' => $request->boolean('is_visible'),
            'rotation_interval_seconds' => (int) $request->input('rotation_interval_seconds'),
            'tips' => collect($request->input('tips', []))
                ->map(fn ($tip, $index) => [
                    'id' => data_get($tip, 'id'),
                    'content' => trim((string) data_get($tip, 'content', '')),
                    'sort_order' => (int) (data_get($tip, 'sort_order') ?: $index + 1),
                    'is_active' => filter_var(data_get($tip, 'is_active', true), FILTER_VALIDATE_BOOL),
                ])
                ->values()
                ->all(),
        ]);
    }

    public static function rules(?ValidationContext $context = null): array
    {
        /** @var SidebarTipPage|null $sidebarTipPage */
        $sidebarTipPage = request()->route('sidebarTipPage');

        return [
            'id' => ['nullable', 'integer'],
            'page_component' => [
                'required',
                'string',
                Rule::in(SidebarTipTargets::components()),
                Rule::unique('sidebar_tip_pages', 'page_component')->ignore($sidebarTipPage?->id),
            ],
            'is_visible' => ['required', 'boolean'],
            'rotation_interval_seconds' => ['required', 'integer', 'min:5', 'max:3600'],
            'tips' => ['required', 'array', 'min:1'],
            'tips.*.id' => ['nullable', 'integer'],
            'tips.*.content' => ['required', 'string', 'max:1000'],
            'tips.*.sort_order' => ['required', 'integer', 'min:1'],
            'tips.*.is_active' => ['required', 'boolean'],
        ];
    }

    public function toRepositoryAttributes(): array
    {
        return [
            'page_component' => $this->page_component,
            'is_visible' => $this->is_visible,
            'rotation_interval_seconds' => $this->rotation_interval_seconds,
        ];
    }

    public function tipAttributes(): array
    {
        return array_map(fn (array $tip) => [
            'id' => $tip['id'],
            'content' => $tip['content'],
            'sort_order' => $tip['sort_order'],
            'is_active' => $tip['is_active'],
        ], $this->tips);
    }
}
