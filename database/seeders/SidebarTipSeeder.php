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
                    'Ellenorizd eloszor a fo mutatokartyakat, hogy gyors kepet kapj az aktualis allapotrol.',
                    'A legutobbi aktivitasi lista segit gyorsan eszrevenni a friss valtozasokat.',
                    'Hasznald a dashboardot napi inditokent, mielott tovabblepsz a reszletes modulokba.',
                    'A sprint attekinto blokk jo kiindulopont a haladas gyors ellenorzesehez.',
                    'Ha egy ertek elter a varttol, innen gyorsan tovabblephetsz a megfelelo menupontba.',
                ],
            ],
            [
                'page_component' => 'Company/Index',
                'tips' => [
                    'A globalis keresovel egyszerre tobb mezo alapjan is gyorsan szukitheted a ceglistat.',
                    'Az oszlopszurok kombinaciojaval konnyebben megtalalod a keresett cegrekordokat.',
                    'Tobb rekord kijelolesevel egyetlen muvelettel is torolhetsz cegeket.',
                    'A lapozast hasznald nagyobb adathalmaznal a lista attekinthetosegenek megtartasahoz.',
                    'Torles vagy szerkesztes elott ellenorizd az aktiv statuszt es az elerhetosegi adatokat.',
                ],
            ],
            [
                'page_component' => 'Profile/Edit',
                'tips' => [
                    'Tartsd naprakeszen a profiladataidat, hogy az ertesitesek biztosan elerjenek.',
                    'Jelszocsere utan erdemes ellenorizni, hogy minden fiokadat helyes maradt-e.',
                    'A profil oldalon erdemes idonkent felulvizsgalni az email-cimet es a nev megjeleneset.',
                    'Ha biztonsagi okbol frissitesz adatot, mentes utan ellenorizd a sikeres modositasokat.',
                    'A profil oldalt hasznald a sajat fiokbeallitasok gyors karbantartasara.',
                ],
            ],
        ];
    }
}
