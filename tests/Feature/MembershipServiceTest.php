<?php

namespace Tests\Feature;

use App\Exceptions\MembershipException;
use App\Models\Customer;
use App\Models\CustomerMembership;
use App\Models\MembershipPlan;
use App\Models\SessionUsage;
use App\Models\Transaction;
use App\Models\User;
use App\Services\MembershipService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembershipServiceTest extends TestCase
{
    use RefreshDatabase;

    private MembershipService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MembershipService;
    }

    public function test_activate_membership_creates_record_with_correct_dates(): void
    {
        Carbon::setTestNow('2024-06-15');

        $customer = Customer::factory()->create();
        $plan = MembershipPlan::factory()->registration()->create();

        // Give customer a registration first
        CustomerMembership::factory()->create([
            'customer_id' => $customer->id,
            'membership_plan_id' => $plan->id,
            'status' => 'active',
        ]);

        $monthlyPlan = MembershipPlan::factory()->monthlyNoEquipment()->create([
            'duration_days' => 30,
            'session_quota' => 8,
        ]);

        $membership = $this->service->activateMembership($customer, $monthlyPlan);

        $this->assertDatabaseHas('customer_memberships', [
            'id' => $membership->id,
            'customer_id' => $customer->id,
            'membership_plan_id' => $monthlyPlan->id,
            'session_quota' => 8,
            'session_used' => 0,
            'status' => 'active',
            'transaction_id' => null,
        ]);

        $this->assertEquals('2024-06-15', $membership->start_date->format('Y-m-d'));
        $this->assertEquals('2024-07-15', $membership->end_date->format('Y-m-d'));

        Carbon::setTestNow();
    }

    public function test_activate_membership_links_transaction_when_provided(): void
    {
        $customer = Customer::factory()->create();
        $plan = MembershipPlan::factory()->registration()->create();

        // Give customer a registration first
        CustomerMembership::factory()->create([
            'customer_id' => $customer->id,
            'membership_plan_id' => $plan->id,
            'status' => 'active',
        ]);

        $monthlyPlan = MembershipPlan::factory()->monthlyNoEquipment()->create();
        $transaction = Transaction::factory()->create(['customer_id' => $customer->id]);

        $membership = $this->service->activateMembership($customer, $monthlyPlan, $transaction);

        $this->assertEquals($transaction->id, $membership->transaction_id);
    }

    public function test_activate_registration_plan_sets_far_future_end_date(): void
    {
        $customer = Customer::factory()->create();
        $plan = MembershipPlan::factory()->registration()->create([
            'duration_days' => 0,
            'session_quota' => 0,
        ]);

        $membership = $this->service->activateMembership($customer, $plan);

        $this->assertEquals('9999-12-31', $membership->end_date->format('Y-m-d'));
        $this->assertEquals(0, $membership->session_quota);
        $this->assertEquals(0, $membership->session_used);
        $this->assertEquals('active', $membership->status);
    }

    public function test_activate_trial_plan_without_registration_succeeds(): void
    {
        $customer = Customer::factory()->create();
        $trialPlan = MembershipPlan::factory()->trial()->create([
            'duration_days' => 7,
            'session_quota' => 1,
        ]);

        $membership = $this->service->activateMembership($customer, $trialPlan);

        $this->assertEquals(1, $membership->session_quota);
        $this->assertEquals('active', $membership->status);
    }

    public function test_activate_monthly_plan_without_registration_throws_exception(): void
    {
        $customer = Customer::factory()->create();
        $monthlyPlan = MembershipPlan::factory()->monthlyNoEquipment()->create();

        $this->expectException(MembershipException::class);
        $this->expectExceptionMessage('Customer must purchase a registration plan before purchasing a monthly or family plan.');

        $this->service->activateMembership($customer, $monthlyPlan);
    }

    public function test_activate_family_plan_without_registration_throws_exception(): void
    {
        $customer = Customer::factory()->create();
        $familyPlan = MembershipPlan::factory()->family()->create();

        $this->expectException(MembershipException::class);

        $this->service->activateMembership($customer, $familyPlan);
    }

    public function test_activate_monthly_with_equipment_without_registration_throws_exception(): void
    {
        $customer = Customer::factory()->create();
        $plan = MembershipPlan::factory()->monthlyWithEquipment()->create();

        $this->expectException(MembershipException::class);

        $this->service->activateMembership($customer, $plan);
    }

    public function test_activate_membership_sets_session_quota_from_plan(): void
    {
        $customer = Customer::factory()->create();
        $registrationPlan = MembershipPlan::factory()->registration()->create();

        CustomerMembership::factory()->create([
            'customer_id' => $customer->id,
            'membership_plan_id' => $registrationPlan->id,
            'status' => 'active',
        ]);

        $monthlyPlan = MembershipPlan::factory()->monthlyNoEquipment()->create([
            'session_quota' => 12,
        ]);

        $membership = $this->service->activateMembership($customer, $monthlyPlan);

        $this->assertEquals(12, $membership->session_quota);
        $this->assertEquals(0, $membership->session_used);
    }

    public function test_extend_membership_while_active_starts_from_day_after_end_date(): void
    {
        Carbon::setTestNow('2024-06-15');

        $customer = Customer::factory()->create();
        $plan = MembershipPlan::factory()->monthlyNoEquipment()->create([
            'duration_days' => 30,
            'session_quota' => 8,
        ]);

        $currentMembership = CustomerMembership::factory()->create([
            'customer_id' => $customer->id,
            'membership_plan_id' => $plan->id,
            'start_date' => '2024-06-01',
            'end_date' => '2024-07-01',
            'session_quota' => 8,
            'session_used' => 3,
            'status' => 'active',
        ]);

        $newMembership = $this->service->extendMembership($currentMembership, $plan);

        $this->assertEquals('2024-07-02', $newMembership->start_date->format('Y-m-d'));
        $this->assertEquals('2024-08-01', $newMembership->end_date->format('Y-m-d'));
        $this->assertEquals(8, $newMembership->session_quota);
        $this->assertEquals(0, $newMembership->session_used);
        $this->assertEquals('active', $newMembership->status);

        Carbon::setTestNow();
    }

    public function test_extend_membership_after_expiration_starts_from_today(): void
    {
        Carbon::setTestNow('2024-07-10');

        $customer = Customer::factory()->create();
        $plan = MembershipPlan::factory()->monthlyNoEquipment()->create([
            'duration_days' => 30,
            'session_quota' => 8,
        ]);

        $expiredMembership = CustomerMembership::factory()->create([
            'customer_id' => $customer->id,
            'membership_plan_id' => $plan->id,
            'start_date' => '2024-06-01',
            'end_date' => '2024-07-01',
            'session_quota' => 8,
            'session_used' => 5,
            'status' => 'expired',
        ]);

        $newMembership = $this->service->extendMembership($expiredMembership, $plan);

        $this->assertEquals('2024-07-10', $newMembership->start_date->format('Y-m-d'));
        $this->assertEquals('2024-08-09', $newMembership->end_date->format('Y-m-d'));
        $this->assertEquals(8, $newMembership->session_quota);
        $this->assertEquals(0, $newMembership->session_used);
        $this->assertEquals('active', $newMembership->status);

        Carbon::setTestNow();
    }

    public function test_extend_membership_does_not_carry_over_unused_quota(): void
    {
        Carbon::setTestNow('2024-07-10');

        $customer = Customer::factory()->create();
        $plan = MembershipPlan::factory()->monthlyNoEquipment()->create([
            'duration_days' => 30,
            'session_quota' => 12,
        ]);

        $expiredMembership = CustomerMembership::factory()->create([
            'customer_id' => $customer->id,
            'membership_plan_id' => $plan->id,
            'start_date' => '2024-06-01',
            'end_date' => '2024-07-01',
            'session_quota' => 12,
            'session_used' => 2,
            'status' => 'expired',
        ]);

        $newMembership = $this->service->extendMembership($expiredMembership, $plan);

        // Fresh quota from plan, no carry-over of the 10 unused sessions
        $this->assertEquals(12, $newMembership->session_quota);
        $this->assertEquals(0, $newMembership->session_used);

        Carbon::setTestNow();
    }

    public function test_extend_membership_links_transaction_when_provided(): void
    {
        Carbon::setTestNow('2024-06-15');

        $customer = Customer::factory()->create();
        $plan = MembershipPlan::factory()->monthlyNoEquipment()->create([
            'duration_days' => 30,
            'session_quota' => 8,
        ]);

        $currentMembership = CustomerMembership::factory()->create([
            'customer_id' => $customer->id,
            'membership_plan_id' => $plan->id,
            'start_date' => '2024-06-01',
            'end_date' => '2024-07-01',
            'status' => 'active',
        ]);

        $transaction = Transaction::factory()->create(['customer_id' => $customer->id]);

        $newMembership = $this->service->extendMembership($currentMembership, $plan, $transaction);

        $this->assertEquals($transaction->id, $newMembership->transaction_id);

        Carbon::setTestNow();
    }

    public function test_extend_membership_creates_new_record_preserving_customer(): void
    {
        Carbon::setTestNow('2024-06-15');

        $customer = Customer::factory()->create();
        $plan = MembershipPlan::factory()->monthlyNoEquipment()->create([
            'duration_days' => 30,
            'session_quota' => 8,
        ]);

        $currentMembership = CustomerMembership::factory()->create([
            'customer_id' => $customer->id,
            'membership_plan_id' => $plan->id,
            'start_date' => '2024-06-01',
            'end_date' => '2024-07-01',
            'status' => 'active',
        ]);

        $newMembership = $this->service->extendMembership($currentMembership, $plan);

        $this->assertNotEquals($currentMembership->id, $newMembership->id);
        $this->assertEquals($customer->id, $newMembership->customer_id);

        Carbon::setTestNow();
    }

    public function test_get_active_membership_returns_active_membership_with_plan_loaded(): void
    {
        Carbon::setTestNow('2024-06-15');

        $customer = Customer::factory()->create();
        $plan = MembershipPlan::factory()->monthlyNoEquipment()->create([
            'duration_days' => 30,
            'session_quota' => 8,
        ]);

        $membership = CustomerMembership::factory()->create([
            'customer_id' => $customer->id,
            'membership_plan_id' => $plan->id,
            'start_date' => '2024-06-01',
            'end_date' => '2024-07-01',
            'session_quota' => 8,
            'session_used' => 2,
            'status' => 'active',
        ]);

        $result = $this->service->getActiveMembership($customer);

        $this->assertNotNull($result);
        $this->assertEquals($membership->id, $result->id);
        $this->assertTrue($result->relationLoaded('membershipPlan'));
        $this->assertEquals($plan->id, $result->membershipPlan->id);

        Carbon::setTestNow();
    }

    public function test_get_active_membership_returns_null_when_no_active_membership(): void
    {
        $customer = Customer::factory()->create();

        $result = $this->service->getActiveMembership($customer);

        $this->assertNull($result);
    }

    public function test_get_active_membership_returns_null_when_membership_is_expired(): void
    {
        $customer = Customer::factory()->create();
        $plan = MembershipPlan::factory()->monthlyNoEquipment()->create();

        CustomerMembership::factory()->expired()->create([
            'customer_id' => $customer->id,
            'membership_plan_id' => $plan->id,
        ]);

        $result = $this->service->getActiveMembership($customer);

        $this->assertNull($result);
    }

    public function test_get_active_membership_returns_null_when_end_date_is_past(): void
    {
        Carbon::setTestNow('2024-07-15');

        $customer = Customer::factory()->create();
        $plan = MembershipPlan::factory()->monthlyNoEquipment()->create();

        CustomerMembership::factory()->create([
            'customer_id' => $customer->id,
            'membership_plan_id' => $plan->id,
            'start_date' => '2024-06-01',
            'end_date' => '2024-07-01',
            'status' => 'active',
        ]);

        $result = $this->service->getActiveMembership($customer);

        $this->assertNull($result);

        Carbon::setTestNow();
    }

    public function test_get_active_membership_returns_latest_end_date_when_multiple_active(): void
    {
        Carbon::setTestNow('2024-06-15');

        $customer = Customer::factory()->create();
        $plan = MembershipPlan::factory()->monthlyNoEquipment()->create([
            'duration_days' => 30,
            'session_quota' => 8,
        ]);

        CustomerMembership::factory()->create([
            'customer_id' => $customer->id,
            'membership_plan_id' => $plan->id,
            'start_date' => '2024-06-01',
            'end_date' => '2024-07-01',
            'session_quota' => 8,
            'session_used' => 2,
            'status' => 'active',
        ]);

        $laterMembership = CustomerMembership::factory()->create([
            'customer_id' => $customer->id,
            'membership_plan_id' => $plan->id,
            'start_date' => '2024-07-02',
            'end_date' => '2024-08-01',
            'session_quota' => 8,
            'session_used' => 0,
            'status' => 'active',
        ]);

        $result = $this->service->getActiveMembership($customer);

        $this->assertNotNull($result);
        $this->assertEquals($laterMembership->id, $result->id);

        Carbon::setTestNow();
    }

    public function test_get_active_membership_ignores_pending_memberships(): void
    {
        Carbon::setTestNow('2024-06-15');

        $customer = Customer::factory()->create();
        $plan = MembershipPlan::factory()->monthlyNoEquipment()->create();

        CustomerMembership::factory()->pending()->create([
            'customer_id' => $customer->id,
            'membership_plan_id' => $plan->id,
            'start_date' => '2024-06-01',
            'end_date' => '2024-07-01',
        ]);

        $result = $this->service->getActiveMembership($customer);

        $this->assertNull($result);

        Carbon::setTestNow();
    }

    public function test_has_registration_returns_true_when_customer_has_active_registration(): void
    {
        $customer = Customer::factory()->create();
        $registrationPlan = MembershipPlan::factory()->registration()->create();

        CustomerMembership::factory()->create([
            'customer_id' => $customer->id,
            'membership_plan_id' => $registrationPlan->id,
            'status' => 'active',
        ]);

        $this->assertTrue($this->service->hasRegistration($customer));
    }

    public function test_has_registration_returns_false_when_no_registration(): void
    {
        $customer = Customer::factory()->create();

        $this->assertFalse($this->service->hasRegistration($customer));
    }

    public function test_has_registration_returns_false_when_registration_is_expired(): void
    {
        $customer = Customer::factory()->create();
        $registrationPlan = MembershipPlan::factory()->registration()->create();

        CustomerMembership::factory()->expired()->create([
            'customer_id' => $customer->id,
            'membership_plan_id' => $registrationPlan->id,
        ]);

        $this->assertFalse($this->service->hasRegistration($customer));
    }

    public function test_check_in_creates_session_usage_and_increments_session_used(): void
    {
        Carbon::setTestNow('2024-06-15 10:30:00');

        $customer = Customer::factory()->create();
        $plan = MembershipPlan::factory()->monthlyNoEquipment()->create([
            'session_quota' => 8,
        ]);

        $membership = CustomerMembership::factory()->create([
            'customer_id' => $customer->id,
            'membership_plan_id' => $plan->id,
            'session_quota' => 8,
            'session_used' => 2,
            'status' => 'active',
        ]);

        $user = User::factory()->create();

        $sessionUsage = $this->service->checkIn($membership, $user->id, 'Morning session');

        $this->assertInstanceOf(SessionUsage::class, $sessionUsage);
        $this->assertEquals($membership->id, $sessionUsage->customer_membership_id);
        $this->assertEquals($customer->id, $sessionUsage->customer_id);
        $this->assertEquals($user->id, $sessionUsage->checked_in_by);
        $this->assertEquals('Morning session', $sessionUsage->notes);
        $this->assertEquals('2024-06-15 10:30:00', $sessionUsage->checked_in_at->format('Y-m-d H:i:s'));

        $membership->refresh();
        $this->assertEquals(3, $membership->session_used);

        Carbon::setTestNow();
    }

    public function test_check_in_throws_exception_when_quota_exhausted(): void
    {
        $customer = Customer::factory()->create();
        $plan = MembershipPlan::factory()->monthlyNoEquipment()->create([
            'session_quota' => 8,
        ]);

        $membership = CustomerMembership::factory()->create([
            'customer_id' => $customer->id,
            'membership_plan_id' => $plan->id,
            'session_quota' => 8,
            'session_used' => 8,
            'status' => 'active',
        ]);

        $this->expectException(MembershipException::class);
        $this->expectExceptionMessage('No remaining sessions. Session quota has been exhausted.');

        $this->service->checkIn($membership);
    }

    public function test_check_in_throws_exception_when_membership_not_active(): void
    {
        $customer = Customer::factory()->create();
        $plan = MembershipPlan::factory()->monthlyNoEquipment()->create([
            'session_quota' => 8,
        ]);

        $membership = CustomerMembership::factory()->expired()->create([
            'customer_id' => $customer->id,
            'membership_plan_id' => $plan->id,
            'session_quota' => 8,
            'session_used' => 2,
        ]);

        $this->expectException(MembershipException::class);
        $this->expectExceptionMessage('Membership is not active.');

        $this->service->checkIn($membership);
    }

    public function test_check_in_works_without_optional_parameters(): void
    {
        Carbon::setTestNow('2024-06-15 14:00:00');

        $customer = Customer::factory()->create();
        $plan = MembershipPlan::factory()->monthlyNoEquipment()->create([
            'session_quota' => 4,
        ]);

        $membership = CustomerMembership::factory()->create([
            'customer_id' => $customer->id,
            'membership_plan_id' => $plan->id,
            'session_quota' => 4,
            'session_used' => 0,
            'status' => 'active',
        ]);

        $sessionUsage = $this->service->checkIn($membership);

        $this->assertNull($sessionUsage->checked_in_by);
        $this->assertNull($sessionUsage->notes);
        $this->assertEquals('2024-06-15 14:00:00', $sessionUsage->checked_in_at->format('Y-m-d H:i:s'));

        $membership->refresh();
        $this->assertEquals(1, $membership->session_used);

        Carbon::setTestNow();
    }

    public function test_check_in_trial_membership_allows_exactly_one_session(): void
    {
        $customer = Customer::factory()->create();
        $trialPlan = MembershipPlan::factory()->trial()->create([
            'session_quota' => 1,
        ]);

        $membership = CustomerMembership::factory()->create([
            'customer_id' => $customer->id,
            'membership_plan_id' => $trialPlan->id,
            'session_quota' => 1,
            'session_used' => 0,
            'status' => 'active',
        ]);

        // First check-in should succeed
        $sessionUsage = $this->service->checkIn($membership);
        $this->assertInstanceOf(SessionUsage::class, $sessionUsage);

        $membership->refresh();
        $this->assertEquals(1, $membership->session_used);

        // Second check-in should fail
        $this->expectException(MembershipException::class);
        $this->expectExceptionMessage('No remaining sessions. Session quota has been exhausted.');

        $this->service->checkIn($membership);
    }

    public function test_check_in_with_pending_membership_throws_exception(): void
    {
        $customer = Customer::factory()->create();
        $plan = MembershipPlan::factory()->monthlyNoEquipment()->create([
            'session_quota' => 8,
        ]);

        $membership = CustomerMembership::factory()->pending()->create([
            'customer_id' => $customer->id,
            'membership_plan_id' => $plan->id,
            'session_quota' => 8,
            'session_used' => 0,
        ]);

        $this->expectException(MembershipException::class);
        $this->expectExceptionMessage('Membership is not active.');

        $this->service->checkIn($membership);
    }

    public function test_can_check_in_returns_true_for_active_membership_with_remaining_quota(): void
    {
        $customer = Customer::factory()->create();
        $plan = MembershipPlan::factory()->monthlyNoEquipment()->create([
            'session_quota' => 8,
        ]);

        $membership = CustomerMembership::factory()->create([
            'customer_id' => $customer->id,
            'membership_plan_id' => $plan->id,
            'session_quota' => 8,
            'session_used' => 3,
            'status' => 'active',
        ]);

        $this->assertTrue($this->service->canCheckIn($membership));
    }

    public function test_can_check_in_returns_false_when_quota_exhausted(): void
    {
        $customer = Customer::factory()->create();
        $plan = MembershipPlan::factory()->monthlyNoEquipment()->create([
            'session_quota' => 8,
        ]);

        $membership = CustomerMembership::factory()->create([
            'customer_id' => $customer->id,
            'membership_plan_id' => $plan->id,
            'session_quota' => 8,
            'session_used' => 8,
            'status' => 'active',
        ]);

        $this->assertFalse($this->service->canCheckIn($membership));
    }

    public function test_can_check_in_returns_false_for_expired_membership(): void
    {
        $customer = Customer::factory()->create();
        $plan = MembershipPlan::factory()->monthlyNoEquipment()->create([
            'session_quota' => 8,
        ]);

        $membership = CustomerMembership::factory()->expired()->create([
            'customer_id' => $customer->id,
            'membership_plan_id' => $plan->id,
            'session_quota' => 8,
            'session_used' => 2,
        ]);

        $this->assertFalse($this->service->canCheckIn($membership));
    }

    public function test_can_check_in_returns_false_for_pending_membership(): void
    {
        $customer = Customer::factory()->create();
        $plan = MembershipPlan::factory()->monthlyNoEquipment()->create([
            'session_quota' => 8,
        ]);

        $membership = CustomerMembership::factory()->pending()->create([
            'customer_id' => $customer->id,
            'membership_plan_id' => $plan->id,
            'session_quota' => 8,
            'session_used' => 0,
        ]);

        $this->assertFalse($this->service->canCheckIn($membership));
    }

    public function test_can_check_in_returns_true_when_zero_sessions_used(): void
    {
        $customer = Customer::factory()->create();
        $plan = MembershipPlan::factory()->monthlyNoEquipment()->create([
            'session_quota' => 8,
        ]);

        $membership = CustomerMembership::factory()->create([
            'customer_id' => $customer->id,
            'membership_plan_id' => $plan->id,
            'session_quota' => 8,
            'session_used' => 0,
            'status' => 'active',
        ]);

        $this->assertTrue($this->service->canCheckIn($membership));
    }

    public function test_expire_overdue_memberships_marks_past_end_date_as_expired(): void
    {
        Carbon::setTestNow('2024-07-15');

        $customer = Customer::factory()->create();
        $plan = MembershipPlan::factory()->monthlyNoEquipment()->create([
            'duration_days' => 30,
            'session_quota' => 8,
        ]);

        // Membership that ended yesterday - should be expired
        CustomerMembership::factory()->create([
            'customer_id' => $customer->id,
            'membership_plan_id' => $plan->id,
            'start_date' => '2024-06-01',
            'end_date' => '2024-07-14',
            'session_quota' => 8,
            'session_used' => 3,
            'status' => 'active',
        ]);

        $count = $this->service->expireOverdueMemberships();

        $this->assertEquals(1, $count);
        $this->assertDatabaseHas('customer_memberships', [
            'customer_id' => $customer->id,
            'status' => 'expired',
        ]);

        Carbon::setTestNow();
    }

    public function test_expire_overdue_memberships_does_not_affect_current_active_memberships(): void
    {
        Carbon::setTestNow('2024-07-15');

        $customer = Customer::factory()->create();
        $plan = MembershipPlan::factory()->monthlyNoEquipment()->create([
            'duration_days' => 30,
            'session_quota' => 8,
        ]);

        // Membership ending today - should NOT be expired (end_date is not past today)
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

        $count = $this->service->expireOverdueMemberships();

        $this->assertEquals(0, $count);
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

    public function test_expire_overdue_memberships_ignores_already_expired_memberships(): void
    {
        Carbon::setTestNow('2024-07-15');

        $customer = Customer::factory()->create();
        $plan = MembershipPlan::factory()->monthlyNoEquipment()->create([
            'duration_days' => 30,
            'session_quota' => 8,
        ]);

        // Already expired membership
        CustomerMembership::factory()->create([
            'customer_id' => $customer->id,
            'membership_plan_id' => $plan->id,
            'start_date' => '2024-05-01',
            'end_date' => '2024-05-31',
            'session_quota' => 8,
            'session_used' => 5,
            'status' => 'expired',
        ]);

        $count = $this->service->expireOverdueMemberships();

        $this->assertEquals(0, $count);

        Carbon::setTestNow();
    }

    public function test_expire_overdue_memberships_expires_regardless_of_remaining_quota(): void
    {
        Carbon::setTestNow('2024-07-15');

        $customer = Customer::factory()->create();
        $plan = MembershipPlan::factory()->monthlyNoEquipment()->create([
            'duration_days' => 30,
            'session_quota' => 8,
        ]);

        // Membership with remaining quota but past end date - should still expire
        CustomerMembership::factory()->create([
            'customer_id' => $customer->id,
            'membership_plan_id' => $plan->id,
            'start_date' => '2024-06-01',
            'end_date' => '2024-07-01',
            'session_quota' => 8,
            'session_used' => 0,
            'status' => 'active',
        ]);

        $count = $this->service->expireOverdueMemberships();

        $this->assertEquals(1, $count);
        $this->assertDatabaseHas('customer_memberships', [
            'customer_id' => $customer->id,
            'status' => 'expired',
            'session_used' => 0,
        ]);

        Carbon::setTestNow();
    }

    public function test_expire_overdue_memberships_returns_zero_when_none_to_expire(): void
    {
        $count = $this->service->expireOverdueMemberships();

        $this->assertEquals(0, $count);
    }

    public function test_expire_overdue_memberships_handles_multiple_overdue_memberships(): void
    {
        Carbon::setTestNow('2024-07-15');

        $plan = MembershipPlan::factory()->monthlyNoEquipment()->create([
            'duration_days' => 30,
            'session_quota' => 8,
        ]);

        // Create 3 overdue memberships for different customers
        for ($i = 0; $i < 3; $i++) {
            $customer = Customer::factory()->create();
            CustomerMembership::factory()->create([
                'customer_id' => $customer->id,
                'membership_plan_id' => $plan->id,
                'start_date' => '2024-06-01',
                'end_date' => '2024-07-01',
                'session_quota' => 8,
                'session_used' => $i,
                'status' => 'active',
            ]);
        }

        $count = $this->service->expireOverdueMemberships();

        $this->assertEquals(3, $count);

        Carbon::setTestNow();
    }

    public function test_expire_overdue_memberships_does_not_affect_pending_memberships(): void
    {
        Carbon::setTestNow('2024-07-15');

        $customer = Customer::factory()->create();
        $plan = MembershipPlan::factory()->monthlyNoEquipment()->create([
            'duration_days' => 30,
            'session_quota' => 8,
        ]);

        // Pending membership with past end date - should NOT be affected
        $pendingMembership = CustomerMembership::factory()->create([
            'customer_id' => $customer->id,
            'membership_plan_id' => $plan->id,
            'start_date' => '2024-06-01',
            'end_date' => '2024-07-01',
            'session_quota' => 8,
            'session_used' => 0,
            'status' => 'pending',
        ]);

        $count = $this->service->expireOverdueMemberships();

        $this->assertEquals(0, $count);
        $this->assertDatabaseHas('customer_memberships', [
            'id' => $pendingMembership->id,
            'status' => 'pending',
        ]);

        Carbon::setTestNow();
    }
}
