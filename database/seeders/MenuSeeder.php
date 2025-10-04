<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('menus')->insert([
            ['title' => 'Dashboard', 'link' => '/dashboard', 'permission' => 'dashboard.view', 'parent_id' => null],
            ['title' => '1. Member Management', 'link' => '/member-management', 'permission' => 'member-management.view', 'parent_id' => null],

            ['title' => '2. Transactions', 'link' => null, 'permission' => 'transactions.view', 'parent_id' => null],

            ['title' => '2.1 Deposit Transactions', 'link' => '/transactions/deposit', 'permission' => 'transactions.deposit.view', 'parent_id' => 3],
            ['title' => '2.2 Withdrawal Transactions', 'link' => '/transactions/withdrawal', 'permission' => 'transactions.withdrawal.view', 'parent_id' => 3],

            ['title' => '3. Referral Program', 'link' => '/referral-program', 'permission' => 'referral-program.view', 'parent_id' => null],

            ['title' => '4. Bank Management', 'link' => '/bank-management', 'permission' => 'bank-management.view', 'parent_id' => null],

            ['title' => '5. Reports', 'link' => null, 'permission' => 'reports.view', 'parent_id' => null],

            ['title' => '5.1 Daily Reports', 'link' => '/reports/daily', 'permission' => 'reports.daily.view', 'parent_id' => 8],
            ['title' => '5.2 Transactions Reports', 'link' => '/reports/transactions', 'permission' => 'reports.transactions.view', 'parent_id' => 8],
            ['title' => '5.3 Promotion Reports', 'link' => '/reports/promotions', 'permission' => 'reports.promotions.view', 'parent_id' => 8],
            ['title' => '5.4 Win/Lose Reports', 'link' => '/reports/win-lose', 'permission' => 'reports.win-lose.view', 'parent_id' => 8],

            ['title' => '6. Bonus Management', 'link' => null, 'permission' => 'bonus-management.view', 'parent_id' => null],

            ['title' => '6.1 Bonus Deposit', 'link' => '/promotions/bonus-deposit', 'permission' => 'bonus-management.deposit.view', 'parent_id' => 13],
            ['title' => '6.2 Share Bonus', 'link' => '/promotions/share-bonus', 'permission' => 'bonus-management.share.view', 'parent_id' => 13],

            ['title' => '7. Website Management', 'link' => '/settings', 'permission' => 'website-management.view', 'parent_id' => null],

            ['title' => '7.1 General', 'link' => '/settings/general', 'permission' => 'website-management.general.view', 'parent_id' => 16],
            ['title' => '7.2 Social Media', 'link' => '/settings/social-media', 'permission' => 'website-management.social-media.view', 'parent_id' => 16],
            ['title' => '7.3 Popup & Sliders', 'link' => '/settings/popup-sliders', 'permission' => 'website-management.popup-sliders.view', 'parent_id' => 16],
            ['title' => '7.4 Promotion Pages', 'link' => '/settings/promotion', 'permission' => 'website-management.promotion.view', 'parent_id' => 16],
            ['title' => '7.5 Seo Management', 'link' => '/settings/seo-management', 'permission' => 'website-management.seo-management.view', 'parent_id' => 16],
            ['title' => '7.6 Domain Meta Tag', 'link' => '/settings/domain-meta', 'permission' => 'website-management.domain-meta.view', 'parent_id' => 16],
            ['title' => '7.7 Theme Website', 'link' => '/settings/theme-website', 'permission' => 'website-management.theme-website.view', 'parent_id' => 16],
            ['title' => '7.8 API', 'link' => '/settings/api', 'permission' => 'website-management.api.view', 'parent_id' => 16],
        ]);
    }
}
