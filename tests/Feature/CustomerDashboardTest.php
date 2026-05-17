<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerMembership;
use App\Models\MembershipPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerDashboardTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->customer = Customer::factory()->create();
    }

    public function test_dashboard_shows_membership_summary_when_active(): void
    {
        $plan = MembershipPlan::factory()->create([
            'name' => '8 Sesi/Bulan - Belum Punya Alat',
            'category' => 'monthly_no_equipment',
            'session_quota' => 8,
        ]);

        CustomerMembership::factory()->create([
            'customer_id' => $this->customer->id,
            'membership_plan_id' => $plan->id,
            'status' => 'active',
            'session_quota' => 8,
            'session_used' => 3,
            'start_date' => now()->subDays(10)->toDateString(),
            'end_date' => now()->addDays(20)->toDateString(),
        ]);

        $response = $this
            ->actingAs($this->customer, 'customer')
            ->get(route('customer.dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Customer/Dashboard')
            ->has('membershipSummary')
            ->where('membershipSummary.plan_name', '8 Sesi/Bulan - Belum Punya Alat')
            ->where('membershipSummary.remaining_sessions', 5)
            ->where('membershipSummary.session_quota', 8)
            ->where('membershipSummary.remaining_days', 20)
            ->where('membershipSummary.is_expiring_soon', false)
        );
    }

    public function test_dashboard_shows_null_membership_summary_when_no_active_membership(): void
    {
        $response = $this
            ->actingAs($this->customer, 'customer')
            ->get(route('customer.dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Customer/Dashboard')
            ->where('membershipSummary', null)
        );
    }

    public function test_dashboard_membership_summary_shows_expiring_soon_when_within_7_days(): void
    {
        $plan = MembershipPlan::factory()->create([
            'name' => 'Paket Trial',
            'category' => 'trial',
            'session_quota' => 1,
        ]);

        CustomerMembership::factory()->create([
            'customer_id' => $this->customer->id,
            'membership_plan_id' => $plan->id,
            'status' => 'active',
            'session_quota' => 1,
            'session_used' => 0,
            'start_date' => now()->subDays(25)->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
        ]);

        $response = $this
            ->actingAs($this->customer, 'customer')
            ->get(route('customer.dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Customer/Dashboard')
            ->has('membershipSummary')
            ->where('membershipSummary.remaining_days', 5)
            ->where('membershipSummary.is_expiring_soon', true)
        );
    }

    public function test_dashboard_does_not_show_expired_membership_in_summary(): void
    {
        $plan = MembershipPlan::factory()->create([
            'category' => 'monthly_no_equipment',
            'session_quota' => 8,
        ]);

        CustomerMembership::factory()->create([
            'customer_id' => $this->customer->id,
            'membership_plan_id' => $plan->id,
            'status' => 'expired',
            'session_quota' => 8,
            'session_used' => 8,
            'start_date' => now()->subDays(40)->toDateString(),
            'end_date' => now()->subDays(10)->toDateString(),
        ]);

        $response = $this
            ->actingAs($this->customer, 'customer')
            ->get(route('customer.dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Customer/Dashboard')
            ->where('membershipSummary', null)
        );
    }

    public function test_dashboard_requires_customer_authentication(): void
    {
        $response = $this->get(route('customer.dashboard'));

        $response->assertRedirect(route('customer.login'));
    }
}
