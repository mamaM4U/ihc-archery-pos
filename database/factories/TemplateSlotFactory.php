<?php

namespace Database\Factories;

use App\Models\CoachWeeklyTemplate;
use App\Models\TemplateSlot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TemplateSlot>
 */
class TemplateSlotFactory extends Factory
{
    protected $model = TemplateSlot::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'template_id' => CoachWeeklyTemplate::factory(),
            'day_of_week' => fake()->numberBetween(0, 6),
            'session_name' => fake()->randomElement(['Pagi', 'Sore']),
            'start_time' => '08:00',
            'end_time' => '10:00',
            'location' => 'Lapangan A',
            'max_capacity' => 10,
            'duration_minutes' => 120,
        ];
    }
}
