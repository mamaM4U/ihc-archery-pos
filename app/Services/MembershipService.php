<?php

namespace App\Services;

use App\Exceptions\MembershipException;
use App\Models\Customer;
use App\Models\CustomerMembership;
use App\Models\MembershipPlan;
use App\Models\SessionUsage;
use App\Models\Transaction;
use Carbon\Carbon;

class MembershipService
{
    /**
     * Activate a membership for a customer.
     *
     * Creates a new CustomerMembership record with:
     * - start_date set to current date
     * - end_date calculated from plan's duration_days (or far future for registration plans)
     * - session_quota from the plan
     * - session_used set to 0
     * - status set to 'active'
     * - transaction linked if provided
     *
     * @throws MembershipException If customer lacks registration for monthly/family plans
     */
    public function activateMembership(Customer $customer, MembershipPlan $plan, ?Transaction $transaction = null): CustomerMembership
    {
        $this->validateRegistrationPrerequisite($customer, $plan);

        $startDate = Carbon::today();
        $endDate = $this->calculateEndDate($startDate, $plan);

        return CustomerMembership::create([
            'customer_id' => $customer->id,
            'membership_plan_id' => $plan->id,
            'transaction_id' => $transaction?->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'session_quota' => $plan->session_quota,
            'session_used' => 0,
            'status' => 'active',
        ]);
    }

    /**
     * Calculate the end date based on the plan's duration.
     *
     * Registration plans (duration_days = 0) get a far future end date since they don't expire.
     */
    private function calculateEndDate(Carbon $startDate, MembershipPlan $plan): Carbon
    {
        if ($plan->duration_days === 0) {
            return Carbon::create(9999, 12, 31);
        }

        return $startDate->copy()->addDays($plan->duration_days);
    }

    /**
     * Validate that the customer has a registration membership before purchasing monthly/family plans.
     *
     * @throws MembershipException
     */
    private function validateRegistrationPrerequisite(Customer $customer, MembershipPlan $plan): void
    {
        $requiresRegistration = in_array($plan->category, [
            'monthly_no_equipment',
            'monthly_with_equipment',
            'family',
        ]);

        if ($requiresRegistration && ! $this->hasRegistration($customer)) {
            throw new MembershipException(
                'Customer must purchase a registration plan before purchasing a monthly or family plan.'
            );
        }
    }

    /**
     * Extend (renew) a membership for a customer.
     *
     * Creates a new CustomerMembership record with:
     * - If current membership is active (end_date >= today): new start_date = current end_date + 1 day
     * - If current membership is expired (end_date < today): new start_date = today
     * - Fresh session_quota from the plan (no carry-over of unused quota)
     * - session_used set to 0
     * - status set to 'active'
     */
    public function extendMembership(CustomerMembership $membership, MembershipPlan $plan, ?Transaction $transaction = null): CustomerMembership
    {
        $today = Carbon::today();

        if ($membership->status === 'active' && $membership->end_date->greaterThanOrEqualTo($today)) {
            $startDate = $membership->end_date->copy()->addDay();
        } else {
            $startDate = $today;
        }

        $endDate = $this->calculateEndDate($startDate, $plan);

        return CustomerMembership::create([
            'customer_id' => $membership->customer_id,
            'membership_plan_id' => $plan->id,
            'transaction_id' => $transaction?->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'session_quota' => $plan->session_quota,
            'session_used' => 0,
            'status' => 'active',
        ]);
    }

    /**
     * Check in a customer for a training session.
     *
     * Validates that the membership is active and has remaining quota,
     * then creates a SessionUsage record and increments session_used.
     *
     * @throws MembershipException If membership is not active or quota is exhausted
     */
    public function checkIn(CustomerMembership $membership, ?int $checkedInBy = null, ?string $notes = null): SessionUsage
    {
        if ($membership->status !== 'active') {
            throw new MembershipException('Membership is not active.');
        }

        if ($membership->session_used >= $membership->session_quota) {
            throw new MembershipException('No remaining sessions. Session quota has been exhausted.');
        }

        $sessionUsage = SessionUsage::create([
            'customer_membership_id' => $membership->id,
            'customer_id' => $membership->customer_id,
            'checked_in_by' => $checkedInBy,
            'checked_in_at' => Carbon::now(),
            'notes' => $notes,
        ]);

        $membership->increment('session_used');

        return $sessionUsage;
    }

    /**
     * Get the customer's current active membership.
     *
     * Returns the active membership with the latest end_date, eager-loading the membershipPlan relationship.
     * Returns null if no active membership exists.
     */
    public function getActiveMembership(Customer $customer): ?CustomerMembership
    {
        return $customer->memberships()
            ->where('status', 'active')
            ->where('end_date', '>=', Carbon::today())
            ->with('membershipPlan')
            ->orderByDesc('end_date')
            ->first();
    }

    /**
     * Check if a membership is eligible for check-in.
     *
     * Returns true if the membership is active AND has remaining session quota.
     */
    public function canCheckIn(CustomerMembership $membership): bool
    {
        return $membership->status === 'active'
            && $membership->session_used < $membership->session_quota;
    }

    /**
     * Check if a customer has a registration type membership record.
     */
    public function hasRegistration(Customer $customer): bool
    {
        return $customer->memberships()
            ->whereHas('membershipPlan', function ($query) {
                $query->where('category', 'registration');
            })
            ->where('status', 'active')
            ->exists();
    }

    /**
     * Expire all overdue memberships.
     *
     * Finds all active memberships where the end_date is past today
     * and marks them as expired, regardless of remaining session quota.
     *
     * @return int The number of memberships that were expired
     */
    public function expireOverdueMemberships(): int
    {
        return CustomerMembership::where('status', 'active')
            ->where('end_date', '<', Carbon::today())
            ->update(['status' => 'expired']);
    }
}
