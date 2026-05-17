<?php

namespace Database\Factories;

use App\Models\MembershipPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MembershipPlan>
 */
class MembershipPlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $category = fake()->randomElement([
            'registration',
            'trial',
            'monthly_no_equipment',
            'monthly_with_equipment',
            'family',
        ]);

        return [
            'name' => fake()->words(3, true).' Plan',
            'category' => $category,
            'price' => fake()->numberBetween(50000, 500000),
            'duration_days' => $category === 'registration' ? 0 : 30,
            'session_quota' => match ($category) {
                'registration' => 0,
                'trial' => 1,
                default => fake()->randomElement([4, 8, 12]),
            },
            'description' => fake()->sentence(),
            'equipment_provided' => in_array($category, ['monthly_no_equipment', 'family']),
            'family_members' => $category === 'family' ? fake()->numberBetween(2, 5) : 1,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the plan is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a registration plan.
     */
    public function registration(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'registration',
            'duration_days' => 0,
            'session_quota' => 0,
            'equipment_provided' => false,
            'family_members' => 1,
        ]);
    }

    /**
     * Create a trial plan.
     */
    public function trial(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'trial',
            'duration_days' => 7,
            'session_quota' => 1,
            'equipment_provided' => true,
            'family_members' => 1,
        ]);
    }

    /**
     * Create a monthly plan without equipment.
     */
    public function monthlyNoEquipment(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'monthly_no_equipment',
            'duration_days' => 30,
            'equipment_provided' => true,
            'family_members' => 1,
        ]);
    }

    /**
     * Create a monthly plan with equipment.
     */
    public function monthlyWithEquipment(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'monthly_with_equipment',
            'duration_days' => 30,
            'equipment_provided' => false,
            'family_members' => 1,
        ]);
    }

    /**
     * Create a family plan.
     */
    public function family(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'family',
            'duration_days' => 30,
            'equipment_provided' => true,
            'family_members' => fake()->numberBetween(2, 5),
        ]);
    }
}
