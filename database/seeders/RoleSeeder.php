<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'dashboard.view',
            'member-management.view',
            'transactions.view',
            'transactions.deposit.view',
            'transactions.withdrawal.view',
            'referral-program.view',
            'bank-management.view',
            'reports.view',
            'reports.daily.view',
            'reports.transactions.view',
            'reports.promotions.view',
            'reports.win-lose.view',
            'bonus-management.view',
            'bonus-management.deposit.view',
            'bonus-management.share.view',
            'website-management.view',
            'website-management.general.view',
            'website-management.social-media.view',
            'website-management.popup-sliders.view',
            'website-management.promotion.view',
            'website-management.seo-management.view',
            'website-management.domain-meta.view',
            'website-management.theme-website.view',
            'website-management.api.view',
        ];


        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'admin']);
        }


        // Administrator
        $adminRole = Role::firstOrCreate(
            ['name' => 'Administrator', 'guard_name' => 'admin']
        );

        // SuperMarketing
        $superMarketingPermission = Permission::whereNotIn('name', [
            'website-management.api.view',
        ])->get();

        $superMarketing = Role::firstOrCreate(
            ['name' => 'SuperMarketing', 'guard_name' => 'admin']
        );

        $superMarketing->syncPermissions($superMarketingPermission);

        $adminRole->syncPermissions(Permission::all());

        // SuperAdmin
        $superAdminRole = Role::firstOrCreate(
            ['name' => 'SuperAdmin', 'guard_name' => 'admin']
        );

        $superAdminPermissions = Permission::whereIn('name', [
            'dashboard.view',
            'member-management.view',
            'transactions.view',
            'transactions.deposit.view',
            'transactions.withdrawal.view',
            'referral-program.view',
            'bank-management.view',
            'reports.view',
            'reports.daily.view',
            'reports.transactions.view',
            'reports.promotions.view',
            'reports.win-lose.view',
            'bonus-management.view',
            'bonus-management.deposit.view',
            'bonus-management.share.view',
            'website-management.view',
            'website-management.general.view',
            'website-management.social-media.view',
            'website-management.popup-sliders.view',
            'website-management.promotion.view',
            'website-management.seo-management.view',
            'website-management.domain-meta.view',
            'website-management.theme-website.view',
        ])->get();

        $superAdminRole->syncPermissions($superAdminPermissions);

        // Admin
        $adminPermissions = Permission::whereNotIn('name', [
            'website-management.view',
            'website-management.general.view',
            'website-management.social-media.view',
            'website-management.popup-sliders.view',
            'website-management.promotion.view',
            'website-management.seo-management.view',
            'website-management.domain-meta.view',
            'website-management.theme-website.view',
            'website-management.api.view',
        ])->get();

        $basicAdminRole = Role::firstOrCreate(
            ['name' => 'Admin', 'guard_name' => 'admin']
        );

        $basicAdminRole->syncPermissions($adminPermissions);

        //CustomerService
        $csPermissions = Permission::whereNotIn('name', [
            'bonus-management.view',
            'bonus-management.deposit.view',
            'bonus-management.share.view',
            'bank-management.view',
            'website-management.view',
            'website-management.general.view',
            'website-management.social-media.view',
            'website-management.popup-sliders.view',
            'website-management.promotion.view',
            'website-management.seo-management.view',
            'website-management.domain-meta.view',
            'website-management.theme-website.view',
            'website-management.api.view',
        ])->get();

        $csRole = Role::firstOrCreate(
            ['name' => 'CustomerService', 'guard_name' => 'admin']
        );

        $csRole->syncPermissions($csPermissions);
    }
}
