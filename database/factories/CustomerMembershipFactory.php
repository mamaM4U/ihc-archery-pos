<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerMembership;
use App\Models\MembershipPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerMembership>
 */
class CustomerMembershipFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $plan = MembershipPlan::factory()->create();

        return [
            'customer_id' => Customer::factory(),
            'membership_plan_id' => $plan->id,
            'transaction_id' => null,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays($plan->duration_days)->toDateString(),
            'session_quota' => $plan->session_quota,
            'session_used' => 0,
            'status' => 'active',
        ];
    }

    /**
     * Indicate that the membership is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => now()->subDays(60)->toDateString(),
            'end_date' => now()->subDays(30)->toDateString(),
            'status' => 'expired',
        ]);
    }

    /**
     * Indicate that the membership is pending payment.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * Indicate that the membership has used sessions.
     */
    public function withUsedSessions(): static
    {
        return $this->state(function (array $attributes) {
            $quota = $attributes['session_quota'] ?? 4;
            $used = fake()->numberBetween(1, max(1, $quota - 1));

            return [
                'session_used' => $used,
            ];
        });
    }
}
