<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SeoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $paths = [
            '/' => 1.00,
            '/info/how-sportsbook' => 0.80,
            '/info/faq-general' => 0.80,
            '/register' => 0.80,
            '/slots' => 0.80,
            '/slots/pragmatic-play' => 0.80,
            '/slots/pgsoft' => 0.80,
            '/slots/joker-gaming' => 0.80,
            '/slots/jili' => 0.80,
            '/slots/playtech' => 0.80,
            '/slots/habanero' => 0.80,
            '/slots/spadegaming' => 0.80,
            '/slots/advantplay' => 0.80,
            '/slots/hacksaw' => 0.80,
            '/slots/relaxgaming' => 0.80,
            '/slots/fastspin' => 0.80,
            '/slots/microgaming' => 0.80,
            '/slots/playNgo' => 0.80,
            '/slots/skywind' => 0.80,
            '/slots/booming' => 0.80,
            '/slots/booongo' => 0.80,
            '/slots/cq9' => 0.80,
            '/slots/evoplay' => 0.80,
            '/slots/playstar' => 0.80,
            '/slots/nolimitcity' => 0.80,
            '/slots/mancala' => 0.80,
            '/slots/eagaming' => 0.80,
            '/slots/red-tiger' => 0.80,
            '/slots/netent' => 0.80,
            '/slots/sbo' => 0.80,
            '/slots/dragoonsoft' => 0.80,
            '/slots/kagaming' => 0.80,
            '/slots/nagagames' => 0.80,
            '/slots/onegame' => 0.80,
            '/slots/apollo777' => 0.80,
            '/slots/fachai' => 0.80,
            '/slots/bgaming' => 0.80,
            '/slots/jdb' => 0.80,
            '/slots/i8' => 0.80,
            '/slots/gmw' => 0.80,
            '/slots/uu' => 0.80,
            '/slots/dodo-gaming' => 0.80,
            '/slots/nextspin' => 0.80,
            '/slots/pegasus' => 0.80,
            '/live' => 0.80,
            '/sports' => 0.80,
            '/casino' => 0.80,
            '/lottery' => 0.80,
            '/poker' => 0.80,
            '/fish-hunter' => 0.80,
            '/fish-hunter/skywind' => 0.80,
            '/fish-hunter/spadegaming' => 0.80,
            '/fish-hunter/cq9' => 0.80,
            '/fish-hunter/joker-gaming' => 0.80,
            '/fish-hunter/jili' => 0.80,
            '/fish-hunter/dragoonsoft' => 0.80,
            '/fish-hunter/kagaming' => 0.80,
            '/fish-hunter/fastspin' => 0.80,
            '/fish-hunter/ks-gaming' => 0.80,
            '/fish-hunter/fachai' => 0.80,
            '/fish-hunter/jdb' => 0.80,
            '/fish-hunter/i8' => 0.80,
            '/e-games' => 0.80,
            '/promotion' => 0.80,
            '/referral' => 0.80,
            '/info/terms-terms_conditions' => 0.80,
            '/info/responsiblegaming' => 0.80,
            '/info/referral' => 0.64,
        ];

        DB::table('seo_management')->insert([
            'robots' => "User-agent: *\nDisallow: /",
            'meta_tag' => null,
            'script_head' => null,
            'script_body' => null,
            'sitemap' => json_encode($paths),
        ]);
    }
}
