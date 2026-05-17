<?php

namespace Tests\Feature;

use App\Models\MembershipPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembershipPlanModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_membership_plan_using_factory(): void
    {
        $plan = MembershipPlan::factory()->create();

        $this->assertDatabaseHas('membership_plans', [
            'id' => $plan->id,
            'name' => $plan->name,
        ]);
    }

    public function test_fillable_attributes_are_mass_assignable(): void
    {
        $plan = MembershipPlan::create([
            'name' => '4 Sesi/Bulan - Belum Punya Alat',
            'category' => 'monthly_no_equipment',
            'price' => 200000,
            'duration_days' => 30,
            'session_quota' => 4,
            'description' => 'Paket bulanan tanpa alat sendiri',
            'equipment_provided' => true,
            'family_members' => 1,
            'is_active' => true,
        ]);

        $this->assertEquals('4 Sesi/Bulan - Belum Punya Alat', $plan->name);
        $this->assertEquals('monthly_no_equipment', $plan->category);
        $this->assertEquals(200000, $plan->price);
        $this->assertEquals(30, $plan->duration_days);
        $this->assertEquals(4, $plan->session_quota);
        $this->assertTrue($plan->equipment_provided);
        $this->assertEquals(1, $plan->family_members);
        $this->assertTrue($plan->is_active);
    }

    public function test_casts_are_applied_correctly(): void
    {
        $plan = MembershipPlan::factory()->create([
            'price' => 150000,
            'duration_days' => 30,
            'session_quota' => 8,
            'equipment_provided' => true,
            'family_members' => 2,
            'is_active' => true,
        ]);

        $plan->refresh();

        $this->assertIsInt($plan->price);
        $this->assertIsInt($plan->duration_days);
        $this->assertIsInt($plan->session_quota);
        $this->assertIsBool($plan->equipment_provided);
        $this->assertIsInt($plan->family_members);
        $this->assertIsBool($plan->is_active);
    }

    public function test_scope_active_filters_only_active_plans(): void
    {
        MembershipPlan::factory()->count(3)->create(['is_active' => true]);
        MembershipPlan::factory()->count(2)->create(['is_active' => false]);

        $activePlans = MembershipPlan::active()->get();

        $this->assertCount(3, $activePlans);
    }

    public function test_factory_registration_state(): void
    {
        $plan = MembershipPlan::factory()->registration()->create();

        $this->assertEquals('registration', $plan->category);
        $this->assertEquals(0, $plan->duration_days);
        $this->assertEquals(0, $plan->session_quota);
        $this->assertFalse($plan->equipment_provided);
    }

    public function test_factory_trial_state(): void
    {
        $plan = MembershipPlan::factory()->trial()->create();

        $this->assertEquals('trial', $plan->category);
        $this->assertEquals(1, $plan->session_quota);
        $this->assertTrue($plan->equipment_provided);
    }

    public function test_factory_inactive_state(): void
    {
        $plan = MembershipPlan::factory()->inactive()->create();

        $this->assertFalse($plan->is_active);
    }

    public function test_factory_family_state(): void
    {
        $plan = MembershipPlan::factory()->family()->create();

        $this->assertEquals('family', $plan->category);
        $this->assertGreaterThanOrEqual(2, $plan->family_members);
        $this->assertTrue($plan->equipment_provided);
    }
}
