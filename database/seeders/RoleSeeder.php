<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // 1. Admin
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions(Permission::all());

        // 2. Coach
        $coach = Role::firstOrCreate(['name' => 'coach']);
        $coach->syncPermissions([
            'dashboard-access',
            'templates-access',
            'templates-create',
            'templates-update',
            'templates-delete',
            'slots-access',
            'slots-create',
            'slots-cancel',
            'bookings-access',
            'bookings-create',
            'bookings-approve',
            'bookings-reject',
            'bookings-cancel',
            'member-data-access',
            'member-data-create',
        ]);

        // 3. Guardian
        $guardian = Role::firstOrCreate(['name' => 'guardian']);
        $guardian->syncPermissions([
            'dashboard-access',
            'bookings-access',
            'bookings-approve',
            'bookings-reject',
            'member-data-access',
        ]);

        // 4. Member
        $member = Role::firstOrCreate(['name' => 'member']);
        $member->syncPermissions([
            'dashboard-access',
            'bookings-access',
            'bookings-create',
            'bookings-cancel',
            'member-data-access',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
