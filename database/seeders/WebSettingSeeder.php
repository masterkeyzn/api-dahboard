<?php

namespace Database\Seeders;

use App\Models\Panel\Admin;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class WebSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $expired = config('seeder.admin_expired');

        DB::table('web_settings')->insert([
            'site_name' => null,
            'site_title' => null,
            'marquee' => null,
            'site_logo' => null,
            'popup' => null,
            'sc_livechat' => null,
            'url_livechat' => null,
            'proggressive_img' => 'https://files.sitestatic.net/progressive_img/oeyou6cVrimVwtTyqm3Zk6zOm7z8S5PFAzZI7Emf.gif',
            'themes' => 'theme-2',
            'favicon' => null,
            'min_deposit' => 35000,
            'max_deposit' => 999999999,
            'min_withdrawal' => 50000,
            'max_withdrawal' => 999999999,
            'unique_code' => "None",
            'is_maintenance' => false,
        ]);
    }
}
