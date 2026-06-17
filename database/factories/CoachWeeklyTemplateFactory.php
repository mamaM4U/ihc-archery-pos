<?php

namespace Database\Factories;

use App\Models\CoachWeeklyTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CoachWeeklyTemplate>
 */
class CoachWeeklyTemplateFactory extends Factory
{
    protected $model = CoachWeeklyTemplate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'coach_id' => User::factory()->coach(),
            'template_name' => fake()->words(3, true),
            'booking_open_days' => 7,
            'is_active' => true,
            'notes' => fake()->sentence(),
        ];
    }

    /**
     * Indicate that the template is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
