<?php

namespace Database\Factories;

use App\Models\SidebarTip;
use App\Models\SidebarTipPage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SidebarTip>
 */
class SidebarTipFactory extends Factory
{
    protected $model = SidebarTip::class;

    public function definition(): array
    {
        return [
            'sidebar_tip_page_id' => SidebarTipPage::factory(),
            'content' => fake()->sentence(),
            'sort_order' => fake()->numberBetween(1, 10),
            'is_active' => true,
        ];
    }
}
