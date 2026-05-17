<?php

namespace Database\Seeders;

use App\Models\MembershipPlan;
use Illuminate\Database\Seeder;

class MembershipPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            // Registration
            [
                'name' => 'Pendaftaran Member',
                'category' => 'registration',
                'price' => 100000,
                'duration_days' => 0,
                'session_quota' => 0,
                'description' => 'Biaya pendaftaran member IHC Archery (satu kali).',
                'equipment_provided' => false,
                'family_members' => 1,
                'is_active' => true,
            ],

            // Trial
            [
                'name' => 'Paket Trial (1 Sesi)',
                'category' => 'trial',
                'price' => 75000,
                'duration_days' => 7,
                'session_quota' => 1,
                'description' => 'Paket coba latihan panahan 1 sesi, termasuk peminjaman alat.',
                'equipment_provided' => true,
                'family_members' => 1,
                'is_active' => true,
            ],

            // Monthly - No Equipment (belum punya alat)
            [
                'name' => '4 Sesi/Bulan - Belum Punya Alat',
                'category' => 'monthly_no_equipment',
                'price' => 250000,
                'duration_days' => 30,
                'session_quota' => 4,
                'description' => 'Paket bulanan 4 sesi latihan, termasuk peminjaman alat.',
                'equipment_provided' => true,
                'family_members' => 1,
                'is_active' => true,
            ],
            [
                'name' => '8 Sesi/Bulan - Belum Punya Alat',
                'category' => 'monthly_no_equipment',
                'price' => 450000,
                'duration_days' => 30,
                'session_quota' => 8,
                'description' => 'Paket bulanan 8 sesi latihan, termasuk peminjaman alat.',
                'equipment_provided' => true,
                'family_members' => 1,
                'is_active' => true,
            ],
            [
                'name' => '12 Sesi/Bulan - Belum Punya Alat',
                'category' => 'monthly_no_equipment',
                'price' => 600000,
                'duration_days' => 30,
                'session_quota' => 12,
                'description' => 'Paket bulanan 12 sesi latihan, termasuk peminjaman alat.',
                'equipment_provided' => true,
                'family_members' => 1,
                'is_active' => true,
            ],

            // Monthly - With Equipment (sudah punya alat)
            [
                'name' => '4 Sesi/Bulan - Sudah Punya Alat',
                'category' => 'monthly_with_equipment',
                'price' => 200000,
                'duration_days' => 30,
                'session_quota' => 4,
                'description' => 'Paket bulanan 4 sesi latihan, tanpa peminjaman alat.',
                'equipment_provided' => false,
                'family_members' => 1,
                'is_active' => true,
            ],
            [
                'name' => '8 Sesi/Bulan - Sudah Punya Alat',
                'category' => 'monthly_with_equipment',
                'price' => 350000,
                'duration_days' => 30,
                'session_quota' => 8,
                'description' => 'Paket bulanan 8 sesi latihan, tanpa peminjaman alat.',
                'equipment_provided' => false,
                'family_members' => 1,
                'is_active' => true,
            ],
            [
                'name' => '12 Sesi/Bulan - Sudah Punya Alat',
                'category' => 'monthly_with_equipment',
                'price' => 500000,
                'duration_days' => 30,
                'session_quota' => 12,
                'description' => 'Paket bulanan 12 sesi latihan, tanpa peminjaman alat.',
                'equipment_provided' => false,
                'family_members' => 1,
                'is_active' => true,
            ],

            // Family
            [
                'name' => 'Paket Keluarga (4 Anggota)',
                'category' => 'family',
                'price' => 800000,
                'duration_days' => 30,
                'session_quota' => 16,
                'description' => 'Paket keluarga untuk 4 anggota, 16 sesi/bulan, termasuk peminjaman alat.',
                'equipment_provided' => true,
                'family_members' => 4,
                'is_active' => true,
            ],
        ];

        foreach ($plans as $plan) {
            MembershipPlan::updateOrCreate(
                ['name' => $plan['name'], 'category' => $plan['category']],
                $plan,
            );
        }
    }
}
