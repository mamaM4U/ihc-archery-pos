<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerMembership;
use App\Models\SessionUsage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SessionUsage>
 */
class SessionUsageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_membership_id' => CustomerMembership::factory(),
            'customer_id' => Customer::factory(),
            'checked_in_by' => null,
            'checked_in_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'notes' => null,
        ];
    }
}
