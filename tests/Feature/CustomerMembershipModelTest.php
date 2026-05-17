<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerMembership;
use App\Models\MembershipPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerMembershipModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_customer_membership_with_factory(): void
    {
        $membership = CustomerMembership::factory()->create();

        $this->assertDatabaseHas('customer_memberships', [
            'id' => $membership->id,
            'status' => 'active',
            'session_used' => 0,
        ]);
    }

    public function test_remaining_sessions_accessor(): void
    {
        $membership = CustomerMembership::factory()->create([
            'session_quota' => 8,
            'session_used' => 3,
        ]);

        $this->assertEquals(5, $membership->remaining_sessions);
    }

    public function test_remaining_sessions_never_negative(): void
    {
        $membership = CustomerMembership::factory()->create([
            'session_quota' => 4,
            'session_used' => 6,
        ]);

        $this->assertEquals(0, $membership->remaining_sessions);
    }

    public function test_remaining_days_accessor(): void
    {
        $membership = CustomerMembership::factory()->create([
            'end_date' => now()->addDays(10)->toDateString(),
        ]);

        $this->assertEquals(10, $membership->remaining_days);
    }

    public function test_remaining_days_never_negative(): void
    {
        $membership = CustomerMembership::factory()->create([
            'end_date' => now()->subDays(5)->toDateString(),
            'status' => 'expired',
        ]);

        $this->assertEquals(0, $membership->remaining_days);
    }

    public function test_is_expiring_soon_when_within_7_days(): void
    {
        $membership = CustomerMembership::factory()->create([
            'end_date' => now()->addDays(5)->toDateString(),
            'status' => 'active',
        ]);

        $this->assertTrue($membership->is_expiring_soon);
    }

    public function test_is_not_expiring_soon_when_more_than_7_days(): void
    {
        $membership = CustomerMembership::factory()->create([
            'end_date' => now()->addDays(20)->toDateString(),
            'status' => 'active',
        ]);

        $this->assertFalse($membership->is_expiring_soon);
    }

    public function test_is_not_expiring_soon_when_not_active(): void
    {
        $membership = CustomerMembership::factory()->expired()->create([
            'end_date' => now()->addDays(3)->toDateString(),
        ]);

        $this->assertFalse($membership->is_expiring_soon);
    }

    public function test_scope_active(): void
    {
        CustomerMembership::factory()->create(['status' => 'active']);
        CustomerMembership::factory()->expired()->create();
        CustomerMembership::factory()->pending()->create();

        $active = CustomerMembership::active()->get();

        $this->assertCount(1, $active);
        $this->assertEquals('active', $active->first()->status);
    }

    public function test_scope_for_customer(): void
    {
        $customer = Customer::factory()->create();
        CustomerMembership::factory()->create(['customer_id' => $customer->id]);
        CustomerMembership::factory()->create(); // different customer

        $memberships = CustomerMembership::forCustomer($customer->id)->get();

        $this->assertCount(1, $memberships);
        $this->assertEquals($customer->id, $memberships->first()->customer_id);
    }

    public function test_belongs_to_customer(): void
    {
        $customer = Customer::factory()->create();
        $membership = CustomerMembership::factory()->create(['customer_id' => $customer->id]);

        $this->assertInstanceOf(Customer::class, $membership->customer);
        $this->assertEquals($customer->id, $membership->customer->id);
    }

    public function test_belongs_to_membership_plan(): void
    {
        $membership = CustomerMembership::factory()->create();

        $this->assertInstanceOf(MembershipPlan::class, $membership->membershipPlan);
    }

    public function test_factory_expired_state(): void
    {
        $membership = CustomerMembership::factory()->expired()->create();

        $this->assertEquals('expired', $membership->status);
        $this->assertTrue($membership->end_date->isPast());
    }

    public function test_factory_pending_state(): void
    {
        $membership = CustomerMembership::factory()->pending()->create();

        $this->assertEquals('pending', $membership->status);
    }

    public function test_factory_with_used_sessions_state(): void
    {
        $membership = CustomerMembership::factory()->withUsedSessions()->create([
            'session_quota' => 8,
        ]);

        $this->assertGreaterThan(0, $membership->session_used);
        $this->assertLessThan($membership->session_quota, $membership->session_used);
    }
}
