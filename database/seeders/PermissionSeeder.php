<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $create = fn ($name) => Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);

        // Dashboard
        $create('dashboard-access');

        // User & Role Management
        $create('users-access');
        $create('users-create');
        $create('users-update');
        $create('users-delete');

        $create('roles-access');
        $create('roles-create');
        $create('roles-update');
        $create('roles-delete');

        $create('permissions-access');

        // Scheduling Templates (Coach/Admin)
        $create('templates-access');
        $create('templates-create');
        $create('templates-update');
        $create('templates-delete');

        // Generated Schedule Slots
        $create('slots-access');
        $create('slots-create');
        $create('slots-cancel');

        // Slot Registrations (Bookings)
        $create('bookings-access');
        $create('bookings-create');
        $create('bookings-approve');
        $create('bookings-reject');
        $create('bookings-cancel');

        // Member Data Tracking
        $create('member-data-access');
        $create('member-data-create');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
