<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;
use Spatie\Activitylog\Facades\Activity;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        Activity::withoutLogs(function (): void {
            Company::factory()->count(12)->create();
        });
    }
}
