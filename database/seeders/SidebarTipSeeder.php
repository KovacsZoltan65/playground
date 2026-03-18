<?php

namespace Database\Seeders;

use App\Models\SidebarTipPage;
use Illuminate\Database\Seeder;
use Spatie\Activitylog\Facades\Activity;

class SidebarTipSeeder extends Seeder
{
    public function run(): void
    {
        Activity::withoutLogs(function (): void {
            foreach ($this->pages() as $page) {
                $sidebarTipPage = SidebarTipPage::query()->updateOrCreate(
                    ['page_component' => $page['page_component']],
                    [
                        'is_visible' => true,
                        'rotation_interval_seconds' => 60,
                    ],
                );

                $sidebarTipPage->tips()->delete();

                foreach ($page['tips'] as $index => $tip) {
                    $sidebarTipPage->tips()->create([
                        'content' => $tip,
                        'sort_order' => $index + 1,
                        'is_active' => true,
                    ]);
                }
            }
        });
    }

    private function pages(): array
    {
        return [
            [
                'page_component' => 'Dashboard',
                'tips' => [
                    'Ellenőrizd először a fő mutatókártyákat, hogy gyors képet kapj az aktuális állapotról.',
                    'A legutóbbi aktivitási lista segít gyorsan észrevenni a friss változásokat.',
                    'Használd a dashboardot napi indítóként, mielőtt továbblépsz a részletes modulokba.',
                    'A sprint attekinto blokk jo kiindulopont a haladas gyors ellenorzesehez.',
                    'Ha egy érték eltér a várttól, innen gyorsan továbbléphetsz a megfelelő menupontba.',
                ],
            ],
            [
                'page_component' => 'Company/Index',
                'tips' => [
                    'A globális keresővel egyszerre több mező alapján is gyorsan szűkítheted a céglistát.',
                    'Az oszlopszűrők kombinációjával könnyebben megtalálod a keresett cégrekordokat.',
                    'Több rekord kijelölésével egyetlen művelettel is törölhetsz cégeket.',
                    'A lapozást használd nagyobb adathalmaznal a lista áttekinthetőségének megtartásához.',
                    'Törles vagy szerkesztés előtt ellenőrizd az aktiv statuszt és az elérhetőségi adatokat.',
                ],
            ],
            [
                'page_component' => 'Profile/Edit',
                'tips' => [
                    'Tartsd naprakészen a profiladataidat, hogy az értesítések biztosan elérjenek.',
                    'Jelszócsere után érdemes ellenőrizni, hogy minden fiókadat helyes maradt-e.',
                    'A profil oldalon érdemes időnként felülvizsgálni az email-címet és a név megjelenését.',
                    'Ha biztonsági okból frissítesz adatot, mentés után ellenőrizd a sikeres módosításokat.',
                    'A profil oldalt használd a saját fiókbeállítások gyors karbantartására.',
                ],
            ],
        ];
    }
}
