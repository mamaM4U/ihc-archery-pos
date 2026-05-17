<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\MembershipPlan;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cashier_id' => User::factory(),
            'cashier_shift_id' => null,
            'customer_id' => Customer::factory(),
            'invoice' => 'INV-'.fake()->unique()->numerify('######'),
            'cash' => 0,
            'change' => 0,
            'discount' => 0,
            'shipping_cost' => 0,
            'grand_total' => fake()->numberBetween(50000, 500000),
            'payment_method' => fake()->randomElement(['cash', 'transfer', 'online']),
            'payment_status' => 'paid',
            'payment_reference' => null,
            'payment_url' => null,
            'bank_account_id' => null,
            'membership_plan_id' => null,
        ];
    }

    /**
     * Indicate that the transaction includes a membership plan.
     */
    public function withMembershipPlan(?MembershipPlan $plan = null): static
    {
        return $this->state(fn (array $attributes) => [
            'membership_plan_id' => $plan?->id ?? MembershipPlan::factory(),
        ]);
    }
}
