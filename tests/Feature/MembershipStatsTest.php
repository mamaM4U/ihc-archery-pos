<?php

namespace Tests\Feature;

use App\Models\CustomerMembership;
use App\Models\MembershipPlan;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembershipStatsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_stats_returns_json_with_expected_keys(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->getJson(route('memberships.stats'));

        $response->assertOk();
        $response->assertJsonStructure([
            'active_members',
            'total_revenue',
            'expiring_soon',
            'session_utilization_rate',
        ]);
    }

    public function test_stats_counts_active_members_correctly(): void
    {
        CustomerMembership::factory()->count(3)->create(['status' => 'active']);
        CustomerMembership::factory()->expired()->create();
        CustomerMembership::factory()->pending()->create();

        $response = $this
            ->actingAs($this->user)
            ->getJson(route('memberships.stats'));

        $response->assertOk();
        $response->assertJson(['active_members' => 3]);
    }

    public function test_stats_calculates_revenue_from_paid_memberships(): void
    {
        $plan = MembershipPlan::factory()->create(['price' => 200000]);
        $transaction = Transaction::factory()->create(['payment_status' => 'paid']);

        CustomerMembership::factory()->create([
            'membership_plan_id' => $plan->id,
            'transaction_id' => $transaction->id,
            'status' => 'active',
        ]);
        CustomerMembership::factory()->create([
            'membership_plan_id' => $plan->id,
            'transaction_id' => $transaction->id,
            'status' => 'expired',
        ]);
        // Pending membership should not count
        CustomerMembership::factory()->pending()->create([
            'membership_plan_id' => $plan->id,
            'transaction_id' => $transaction->id,
        ]);

        $response = $this
            ->actingAs($this->user)
            ->getJson(route('memberships.stats'));

        $response->assertOk();
        $response->assertJson(['total_revenue' => 400000]);
    }

    public function test_stats_counts_expiring_soon_memberships(): void
    {
        // Expiring in 3 days — should count
        CustomerMembership::factory()->create([
            'status' => 'active',
            'end_date' => now()->addDays(3)->toDateString(),
        ]);
        // Expiring in 7 days — should count
        CustomerMembership::factory()->create([
            'status' => 'active',
            'end_date' => now()->addDays(7)->toDateString(),
        ]);
        // Expiring in 10 days — should NOT count
        CustomerMembership::factory()->create([
            'status' => 'active',
            'end_date' => now()->addDays(10)->toDateString(),
        ]);

        $response = $this
            ->actingAs($this->user)
            ->getJson(route('memberships.stats'));

        $response->assertOk();
        $response->assertJson(['expiring_soon' => 2]);
    }

    public function test_stats_calculates_session_utilization_rate(): void
    {
        CustomerMembership::factory()->create([
            'status' => 'active',
            'session_quota' => 8,
            'session_used' => 4,
        ]);
        CustomerMembership::factory()->create([
            'status' => 'active',
            'session_quota' => 12,
            'session_used' => 6,
        ]);

        // Total quota: 20, total used: 10 => 50%
        $response = $this
            ->actingAs($this->user)
            ->getJson(route('memberships.stats'));

        $response->assertOk();
        $response->assertJson(['session_utilization_rate' => 50.0]);
    }

    public function test_stats_returns_zero_utilization_when_no_active_memberships(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->getJson(route('memberships.stats'));

        $response->assertOk();
        $response->assertJson([
            'active_members' => 0,
            'total_revenue' => 0,
            'expiring_soon' => 0,
            'session_utilization_rate' => 0,
        ]);
    }
}
