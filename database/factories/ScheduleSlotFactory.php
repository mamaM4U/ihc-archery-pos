<?php

namespace Database\Factories;

use App\Models\ScheduleSlot;
use App\Models\TemplateSlot;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScheduleSlot>
 */
class ScheduleSlotFactory extends Factory
{
    protected $model = ScheduleSlot::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'coach_id' => User::factory()->coach(),
            'template_slot_id' => TemplateSlot::factory(),
            'slot_date' => now()->toDateString(),
            'session_name' => fake()->randomElement(['Pagi', 'Sore']),
            'start_time' => '08:00',
            'end_time' => '10:00',
            'location' => 'Lapangan A',
            'max_capacity' => 10,
            'current_bookings' => 0,
            'status' => 'available',
        ];
    }
}
