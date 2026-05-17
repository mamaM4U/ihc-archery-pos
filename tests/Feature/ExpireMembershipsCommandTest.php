<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerMembership;
use App\Models\MembershipPlan;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpireMembershipsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_expires_memberships_past_end_date(): void
    {
        Carbon::setTestNow('2024-07-15');

        $customer = Customer::factory()->create();
        $plan = MembershipPlan::factory()->monthlyNoEquipment()->create([
            'duration_days' => 30,
            'session_quota' => 8,
        ]);

        $membership = CustomerMembership::factory()->create([
            'customer_id' => $customer->id,
            'membership_plan_id' => $plan->id,
            'start_date' => '2024-06-01',
            'end_date' => '2024-07-14',
            'session_quota' => 8,
            'session_used' => 3,
            'status' => 'active',
        ]);

        $this->artisan('memberships:expire')
            ->expectsOutputToContain('Expired 1 overdue membership(s).')
            ->assertSuccessful();

        $this->assertDatabaseHas('customer_memberships', [
            'id' => $membership->id,
            'status' => 'expired',
        ]);

        Carbon::setTestNow();
    }

    public function test_command_does_not_expire_memberships_within_period(): void
    {
        Carbon::setTestNow('2024-07-15');

        $customer = Customer::factory()->create();
        $plan = MembershipPlan::factory()->monthlyNoEquipment()->create([
            'duration_days' => 30,
            'session_quota' => 8,
        ]);

        // Membership ending today - should NOT be expired
        $todayMembership = CustomerMembership::factory()->create([
            'customer_id' => $customer->id,
            'membership_plan_id' => $plan->id,
            'start_date' => '2024-06-15',
            'end_date' => '2024-07-15',
            'session_quota' => 8,
            'session_used' => 2,
            'status' => 'active',
        ]);

        // Membership ending in the future - should NOT be expired
        $futureMembership = CustomerMembership::factory()->create([
            'customer_id' => $customer->id,
            'membership_plan_id' => $plan->id,
            'start_date' => '2024-07-01',
            'end_date' => '2024-07-31',
            'session_quota' => 8,
            'session_used' => 0,
            'status' => 'active',
        ]);

        $this->artisan('memberships:expire')
            ->expectsOutputToContain('Expired 0 overdue membership(s).')
            ->assertSuccessful();

        $this->assertDatabaseHas('customer_memberships', [
            'id' => $todayMembership->id,
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('customer_memberships', [
            'id' => $futureMembership->id,
            'status' => 'active',
        ]);

        Carbon::setTestNow();
    }

    public function test_command_does_not_affect_already_expired_memberships(): void
    {
        Carbon::setTestNow('2024-07-15');

        $customer = Customer::factory()->create();
        $plan = MembershipPlan::factory()->monthlyNoEquipment()->create([
            'duration_days' => 30,
            'session_quota' => 8,
        ]);

        $expiredMembership = CustomerMembership::factory()->create([
            'customer_id' => $customer->id,
            'membership_plan_id' => $plan->id,
            'start_date' => '2024-05-01',
            'end_date' => '2024-05-31',
            'session_quota' => 8,
            'session_used' => 5,
            'status' => 'expired',
        ]);

        $this->artisan('memberships:expire')
            ->expectsOutputToContain('Expired 0 overdue membership(s).')
            ->assertSuccessful();

        $this->assertDatabaseHas('customer_memberships', [
            'id' => $expiredMembership->id,
            'status' => 'expired',
        ]);

        Carbon::setTestNow();
    }

    public function test_command_does_not_affect_pending_memberships(): void
    {
        Carbon::setTestNow('2024-07-15');

        $customer = Customer::factory()->create();
        $plan = MembershipPlan::factory()->monthlyNoEquipment()->create([
            'duration_days' => 30,
            'session_quota' => 8,
        ]);

        $pendingMembership = CustomerMembership::factory()->create([
            'customer_id' => $customer->id,
            'membership_plan_id' => $plan->id,
            'start_date' => '2024-06-01',
            'end_date' => '2024-07-01',
            'session_quota' => 8,
            'session_used' => 0,
            'status' => 'pending',
        ]);

        $this->artisan('memberships:expire')
            ->expectsOutputToContain('Expired 0 overdue membership(s).')
            ->assertSuccessful();

        $this->assertDatabaseHas('customer_memberships', [
            'id' => $pendingMembership->id,
            'status' => 'pending',
        ]);

        Carbon::setTestNow();
    }

    public function test_command_outputs_count_of_expired_memberships(): void
    {
        Carbon::setTestNow('2024-07-15');

        $customer = Customer::factory()->create();
        $plan = MembershipPlan::factory()->monthlyNoEquipment()->create([
            'duration_days' => 30,
            'session_quota' => 8,
        ]);

        // Create 3 overdue active memberships
        for ($i = 0; $i < 3; $i++) {
            CustomerMembership::factory()->create([
                'customer_id' => $customer->id,
                'membership_plan_id' => $plan->id,
                'start_date' => '2024-05-01',
                'end_date' => '2024-06-01',
                'session_quota' => 8,
                'session_used' => $i,
                'status' => 'active',
            ]);
        }

        $this->artisan('memberships:expire')
            ->expectsOutputToContain('Expired 3 overdue membership(s).')
            ->assertSuccessful();

        Carbon::setTestNow();
    }

    public function test_command_returns_success_exit_code(): void
    {
        $this->artisan('memberships:expire')
            ->assertExitCode(0);
    }
}
