<?php

namespace Database\Factories;

use App\Models\SidebarTipPage;
use App\Support\SidebarTips\SidebarTipTargets;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SidebarTipPage>
 */
class SidebarTipPageFactory extends Factory
{
    protected $model = SidebarTipPage::class;

    public function definition(): array
    {
        return [
            'page_component' => fake()->unique()->randomElement(SidebarTipTargets::components()),
            'is_visible' => true,
            'rotation_interval_seconds' => 60,
        ];
    }
}
