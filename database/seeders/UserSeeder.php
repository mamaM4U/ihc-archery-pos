<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // 1. Admin
        $admin = User::updateOrCreate(
            ['email' => 'admin@ihcarchery.com'],
            [
                'name' => 'Admin IHC Archery',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'is_active' => true,
                'phone' => '081234567890',
            ]
        );
        $adminRole = Role::where('name', 'admin')->first();
        $superAdminRole = Role::where('name', 'super-admin')->first();
        if ($adminRole) {
            $admin->syncRoles([$adminRole->name]);
        } elseif ($superAdminRole) {
            $admin->syncRoles([$superAdminRole->name]);
        }
        $admin->syncPermissions(Permission::all());

        // 2. Coach
        $coach = User::updateOrCreate(
            ['email' => 'coach@ihcarchery.com'],
            [
                'name' => 'Pelatih Panahan',
                'password' => Hash::make('password'),
                'role' => 'coach',
                'is_active' => true,
                'phone' => '081234567891',
            ]
        );
        $coachRole = Role::where('name', 'coach')->first();
        if ($coachRole) {
            $coach->syncRoles([$coachRole->name]);
        }

        // 3. Guardian
        $guardian = User::updateOrCreate(
            ['email' => 'guardian@ihcarchery.com'],
            [
                'name' => 'Orang Tua / Wali',
                'password' => Hash::make('password'),
                'role' => 'guardian',
                'is_active' => true,
                'phone' => '081234567892',
            ]
        );
        $guardianRole = Role::where('name', 'guardian')->first();
        if ($guardianRole) {
            $guardian->syncRoles([$guardianRole->name]);
        }

        // 4. Member (Atlet/Siswa)
        $member = User::updateOrCreate(
            ['email' => 'member@ihcarchery.com'],
            [
                'name' => 'Atlet Panahan',
                'password' => Hash::make('password'),
                'role' => 'member',
                'is_active' => true,
                'phone' => '081234567893',
            ]
        );
        $memberRole = Role::where('name', 'member')->first();
        if ($memberRole) {
            $member->syncRoles([$memberRole->name]);
        }

        // Setup Relasi untuk testing
        // Hubungkan Guardian ke Member
        $guardian->members()->syncWithoutDetaching([
            $member->id => ['can_approve_booking' => true],
        ]);

        // Hubungkan Coach ke Member
        $coach->coachMembers()->syncWithoutDetaching([
            $member->id,
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
