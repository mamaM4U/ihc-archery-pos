<?php

namespace Tests\Feature;

use App\Models\CustomerMembership;
use App\Models\MembershipPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembershipPlanTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_index_page_loads_and_displays_membership_plans(): void
    {
        MembershipPlan::factory()->count(3)->create();

        $response = $this
            ->actingAs($this->user)
            ->get(route('membership-plans.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard/MembershipPlans/Index')
            ->has('membershipPlans.data', 3)
        );
    }

    public function test_index_page_supports_search_filter(): void
    {
        MembershipPlan::factory()->create(['name' => 'Paket Bulanan']);
        MembershipPlan::factory()->create(['name' => 'Paket Trial']);

        $response = $this
            ->actingAs($this->user)
            ->get(route('membership-plans.index', ['search' => 'Bulanan']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard/MembershipPlans/Index')
            ->has('membershipPlans.data', 1)
        );
    }

    public function test_create_page_loads(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->get(route('membership-plans.create'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard/MembershipPlans/Create')
        );
    }

    public function test_store_creates_a_new_membership_plan_with_valid_data(): void
    {
        $data = [
            'name' => '8 Sesi/Bulan - Belum Punya Alat',
            'category' => 'monthly_no_equipment',
            'price' => 350000,
            'duration_days' => 30,
            'session_quota' => 8,
            'description' => 'Paket bulanan 8 sesi',
            'equipment_provided' => true,
            'family_members' => 1,
            'is_active' => true,
        ];

        $response = $this
            ->actingAs($this->user)
            ->post(route('membership-plans.store'), $data);

        $response->assertRedirect(route('membership-plans.index'));

        $this->assertDatabaseHas('membership_plans', [
            'name' => '8 Sesi/Bulan - Belum Punya Alat',
            'category' => 'monthly_no_equipment',
            'price' => 350000,
            'duration_days' => 30,
            'session_quota' => 8,
            'equipment_provided' => true,
            'family_members' => 1,
            'is_active' => true,
        ]);
    }

    public function test_store_validation_requires_name(): void
    {
        $data = [
            'category' => 'monthly_no_equipment',
            'price' => 350000,
            'duration_days' => 30,
            'session_quota' => 8,
            'equipment_provided' => true,
            'family_members' => 1,
            'is_active' => true,
        ];

        $response = $this
            ->actingAs($this->user)
            ->post(route('membership-plans.store'), $data);

        $response->assertSessionHasErrors('name');
    }

    public function test_store_validation_requires_category(): void
    {
        $data = [
            'name' => 'Test Plan',
            'price' => 350000,
            'duration_days' => 30,
            'session_quota' => 8,
            'equipment_provided' => true,
            'family_members' => 1,
            'is_active' => true,
        ];

        $response = $this
            ->actingAs($this->user)
            ->post(route('membership-plans.store'), $data);

        $response->assertSessionHasErrors('category');
    }

    public function test_store_validation_rejects_invalid_category(): void
    {
        $data = [
            'name' => 'Test Plan',
            'category' => 'invalid_category',
            'price' => 350000,
            'duration_days' => 30,
            'session_quota' => 8,
            'equipment_provided' => true,
            'family_members' => 1,
            'is_active' => true,
        ];

        $response = $this
            ->actingAs($this->user)
            ->post(route('membership-plans.store'), $data);

        $response->assertSessionHasErrors('category');
    }

    public function test_store_validation_requires_price(): void
    {
        $data = [
            'name' => 'Test Plan',
            'category' => 'monthly_no_equipment',
            'duration_days' => 30,
            'session_quota' => 8,
            'equipment_provided' => true,
            'family_members' => 1,
            'is_active' => true,
        ];

        $response = $this
            ->actingAs($this->user)
            ->post(route('membership-plans.store'), $data);

        $response->assertSessionHasErrors('price');
    }

    public function test_store_validation_family_category_requires_family_members(): void
    {
        $data = [
            'name' => 'Paket Keluarga',
            'category' => 'family',
            'price' => 500000,
            'duration_days' => 30,
            'session_quota' => 12,
            'equipment_provided' => true,
            'is_active' => true,
        ];

        $response = $this
            ->actingAs($this->user)
            ->post(route('membership-plans.store'), $data);

        $response->assertSessionHasErrors('family_members');
    }

    public function test_edit_page_loads_with_existing_plan_data(): void
    {
        $plan = MembershipPlan::factory()->create([
            'name' => 'Paket Edit Test',
        ]);

        $response = $this
            ->actingAs($this->user)
            ->get(route('membership-plans.edit', $plan));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard/MembershipPlans/Edit')
            ->has('membershipPlan')
            ->where('membershipPlan.id', $plan->id)
            ->where('membershipPlan.name', 'Paket Edit Test')
        );
    }

    public function test_update_modifies_an_existing_plan(): void
    {
        $plan = MembershipPlan::factory()->monthlyNoEquipment()->create([
            'name' => 'Old Name',
            'price' => 200000,
        ]);

        $data = [
            'name' => 'Updated Name',
            'category' => 'monthly_no_equipment',
            'price' => 300000,
            'duration_days' => 30,
            'session_quota' => 8,
            'description' => 'Updated description',
            'equipment_provided' => true,
            'family_members' => 1,
            'is_active' => true,
        ];

        $response = $this
            ->actingAs($this->user)
            ->put(route('membership-plans.update', $plan), $data);

        $response->assertRedirect(route('membership-plans.index'));

        $plan->refresh();
        $this->assertEquals('Updated Name', $plan->name);
        $this->assertEquals(300000, $plan->price);
        $this->assertEquals(8, $plan->session_quota);
    }

    public function test_destroy_removes_a_plan_without_members(): void
    {
        $plan = MembershipPlan::factory()->create();

        $response = $this
            ->actingAs($this->user)
            ->delete(route('membership-plans.destroy', $plan));

        $response->assertRedirect(route('membership-plans.index'));
        $this->assertDatabaseMissing('membership_plans', ['id' => $plan->id]);
    }

    public function test_destroy_rejects_deletion_when_plan_has_members(): void
    {
        $plan = MembershipPlan::factory()->create();
        CustomerMembership::factory()->create([
            'membership_plan_id' => $plan->id,
        ]);

        $response = $this
            ->actingAs($this->user)
            ->delete(route('membership-plans.destroy', $plan));

        $response->assertSessionHasErrors('membershipPlan');
        $this->assertDatabaseHas('membership_plans', ['id' => $plan->id]);
    }

    public function test_toggle_active_status_via_update(): void
    {
        $plan = MembershipPlan::factory()->create(['is_active' => true]);

        $data = [
            'name' => $plan->name,
            'category' => $plan->category,
            'price' => $plan->price,
            'duration_days' => $plan->duration_days,
            'session_quota' => $plan->session_quota,
            'equipment_provided' => $plan->equipment_provided,
            'family_members' => $plan->family_members,
            'is_active' => false,
        ];

        $response = $this
            ->actingAs($this->user)
            ->put(route('membership-plans.update', $plan), $data);

        $response->assertRedirect(route('membership-plans.index'));

        $plan->refresh();
        $this->assertFalse($plan->is_active);
    }

    public function test_store_trial_category_with_quota_one(): void
    {
        $data = [
            'name' => 'Trial Plan',
            'category' => 'trial',
            'price' => 50000,
            'duration_days' => 7,
            'session_quota' => 1,
            'description' => 'Coba 1 sesi',
            'equipment_provided' => true,
            'family_members' => 1,
            'is_active' => true,
        ];

        $response = $this
            ->actingAs($this->user)
            ->post(route('membership-plans.store'), $data);

        $response->assertRedirect(route('membership-plans.index'));

        $this->assertDatabaseHas('membership_plans', [
            'category' => 'trial',
            'session_quota' => 1,
        ]);
    }

    public function test_store_registration_category_with_zero_quota(): void
    {
        $data = [
            'name' => 'Registration Plan',
            'category' => 'registration',
            'price' => 100000,
            'duration_days' => 0,
            'session_quota' => 0,
            'description' => 'Pendaftaran member',
            'equipment_provided' => false,
            'family_members' => 1,
            'is_active' => true,
        ];

        $response = $this
            ->actingAs($this->user)
            ->post(route('membership-plans.store'), $data);

        $response->assertRedirect(route('membership-plans.index'));

        $this->assertDatabaseHas('membership_plans', [
            'category' => 'registration',
            'session_quota' => 0,
            'duration_days' => 0,
        ]);
    }

    public function test_store_family_category_with_family_members(): void
    {
        $data = [
            'name' => 'Paket Keluarga 4 Orang',
            'category' => 'family',
            'price' => 600000,
            'duration_days' => 30,
            'session_quota' => 16,
            'description' => 'Paket keluarga 4 anggota',
            'equipment_provided' => true,
            'family_members' => 4,
            'is_active' => true,
        ];

        $response = $this
            ->actingAs($this->user)
            ->post(route('membership-plans.store'), $data);

        $response->assertRedirect(route('membership-plans.index'));

        $this->assertDatabaseHas('membership_plans', [
            'category' => 'family',
            'family_members' => 4,
            'session_quota' => 16,
        ]);
    }
}
