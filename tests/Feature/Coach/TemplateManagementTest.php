<?php

namespace Tests\Feature\Coach;

use App\Models\CoachWeeklyTemplate;
use App\Models\ScheduleSlot;
use App\Models\TemplateSlot;
use App\Models\User;
use App\Services\ScheduleGenerationService;
use Carbon\Carbon;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $coach;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->coach = User::factory()->coach()->create([
            'email' => 'coach@test.com',
        ]);

        $this->admin = User::factory()->admin()->create([
            'email' => 'admin@test.com',
        ]);
    }

    public function test_coach_can_view_templates_list(): void
    {
        $template = CoachWeeklyTemplate::factory()->create([
            'coach_id' => $this->coach->id,
            'template_name' => 'Jadwal Coach Utama',
        ]);

        $response = $this->actingAs($this->coach)
            ->get(route('templates.index'));

        $response->assertStatus(200);
        $response->assertSee('Jadwal Coach Utama');
    }

    public function test_coach_can_create_weekly_template(): void
    {
        $payload = [
            'template_name' => 'Jadwal Rutin',
            'booking_open_days' => 7,
            'is_active' => true,
            'notes' => 'Catatan latihan',
            'slots' => [
                [
                    'day_of_week' => 1, // Monday
                    'session_name' => 'Pagi',
                    'start_time' => '08:00',
                    'end_time' => '10:00',
                    'location' => 'Lapangan A',
                    'max_capacity' => 8,
                    'duration_minutes' => 120,
                ],
                [
                    'day_of_week' => 3, // Wednesday
                    'session_name' => 'Sore',
                    'start_time' => '16:00',
                    'end_time' => '18:00',
                    'location' => 'Lapangan B',
                    'max_capacity' => 10,
                    'duration_minutes' => 120,
                ],
            ],
        ];

        $response = $this->actingAs($this->coach)
            ->post(route('templates.store'), $payload);

        $response->assertRedirect(route('templates.index'));

        $this->assertDatabaseHas('coach_weekly_templates', [
            'coach_id' => $this->coach->id,
            'template_name' => 'Jadwal Rutin',
            'is_active' => true,
        ]);

        $template = CoachWeeklyTemplate::where('template_name', 'Jadwal Rutin')->first();
        $this->assertCount(2, $template->templateSlots);
    }

    public function test_coach_can_update_weekly_template(): void
    {
        $template = CoachWeeklyTemplate::factory()->create([
            'coach_id' => $this->coach->id,
            'template_name' => 'Template Lama',
        ]);

        TemplateSlot::factory()->create([
            'template_id' => $template->id,
            'day_of_week' => 1,
        ]);

        $payload = [
            'template_name' => 'Template Baru',
            'booking_open_days' => 14,
            'is_active' => true,
            'notes' => 'Terbaru',
            'slots' => [
                [
                    'day_of_week' => 2, // Tuesday
                    'session_name' => 'Sesi 1',
                    'start_time' => '09:00',
                    'end_time' => '11:00',
                    'location' => 'Lapangan C',
                    'max_capacity' => 5,
                    'duration_minutes' => 120,
                ],
            ],
        ];

        $response = $this->actingAs($this->coach)
            ->put(route('templates.update', $template->id), $payload);

        $response->assertRedirect(route('templates.index'));

        $updated = $template->fresh();
        $this->assertEquals('Template Baru', $updated->template_name);
        $this->assertEquals(14, $updated->booking_open_days);
        $this->assertCount(1, $updated->templateSlots);
        $this->assertEquals(2, $updated->templateSlots->first()->day_of_week);
    }

    public function test_coach_can_delete_weekly_template(): void
    {
        $template = CoachWeeklyTemplate::factory()->create([
            'coach_id' => $this->coach->id,
        ]);

        TemplateSlot::factory()->create([
            'template_id' => $template->id,
        ]);

        $response = $this->actingAs($this->coach)
            ->delete(route('templates.destroy', $template->id));

        $response->assertRedirect();
        $this->assertDatabaseMissing('coach_weekly_templates', [
            'id' => $template->id,
        ]);
        $this->assertDatabaseMissing('template_slots', [
            'template_id' => $template->id,
        ]);
    }

    public function test_coach_cannot_view_or_modify_other_coach_template(): void
    {
        $otherCoach = User::factory()->coach()->create();
        $template = CoachWeeklyTemplate::factory()->create([
            'coach_id' => $otherCoach->id,
            'template_name' => 'Template Coach Lain',
        ]);

        // Access Index: Coach should not see other coach's template
        $response = $this->actingAs($this->coach)->get(route('templates.index'));
        $response->assertStatus(200);
        $response->assertDontSee('Template Coach Lain');

        // Edit
        $response = $this->actingAs($this->coach)->get(route('templates.edit', $template->id));
        $response->assertStatus(403);

        // Update
        $payload = [
            'template_name' => 'Hacked',
            'booking_open_days' => 7,
            'is_active' => true,
            'slots' => [],
        ];
        $response = $this->actingAs($this->coach)->put(route('templates.update', $template->id), $payload);
        $response->assertStatus(403);

        // Delete
        $response = $this->actingAs($this->coach)->delete(route('templates.destroy', $template->id));
        $response->assertStatus(403);
    }

    public function test_lazy_generation_creates_slots_correctly(): void
    {
        $template = CoachWeeklyTemplate::factory()->create([
            'coach_id' => $this->coach->id,
            'is_active' => true,
        ]);

        // Monday Slot
        $slot1 = TemplateSlot::factory()->create([
            'template_id' => $template->id,
            'day_of_week' => 1, // Monday
            'session_name' => 'Pagi',
            'start_time' => '08:00',
            'end_time' => '10:00',
            'location' => 'Lapangan Utama',
            'max_capacity' => 6,
        ]);

        // Wednesday Slot
        $slot2 = TemplateSlot::factory()->create([
            'template_id' => $template->id,
            'day_of_week' => 3, // Wednesday
            'session_name' => 'Sore',
            'start_time' => '16:00',
            'end_time' => '18:00',
        ]);

        $service = new ScheduleGenerationService;

        // Let's generate for a Monday (June 22, 2026 is Monday)
        $monday = Carbon::parse('2026-06-22');
        $slots = $service->generateForDate($this->coach->id, $monday);

        $this->assertCount(1, $slots);
        $this->assertEquals('Pagi', $slots->first()->session_name);
        $this->assertEquals('08:00:00', $slots->first()->start_time);
        $this->assertEquals('Lapangan Utama', $slots->first()->location);
        $this->assertEquals(6, $slots->first()->max_capacity);

        $this->assertDatabaseHas('schedule_slots', [
            'coach_id' => $this->coach->id,
            'slot_date' => '2026-06-22',
            'session_name' => 'Pagi',
        ]);
    }

    public function test_lazy_generation_does_not_duplicate_existing_slots(): void
    {
        $template = CoachWeeklyTemplate::factory()->create([
            'coach_id' => $this->coach->id,
            'is_active' => true,
        ]);

        $templateSlot = TemplateSlot::factory()->create([
            'template_id' => $template->id,
            'day_of_week' => 1, // Monday
            'session_name' => 'Pagi',
        ]);

        $service = new ScheduleGenerationService;
        $monday = Carbon::parse('2026-06-22');

        // First run
        $service->generateForDate($this->coach->id, $monday);

        // Assert 1 record exists
        $this->assertEquals(1, ScheduleSlot::where('coach_id', $this->coach->id)->where('slot_date', '2026-06-22')->count());

        // Second run
        $service->generateForDate($this->coach->id, $monday);

        // Assert still 1 record exists (no duplicate)
        $this->assertEquals(1, ScheduleSlot::where('coach_id', $this->coach->id)->where('slot_date', '2026-06-22')->count());
    }

    public function test_lazy_generation_handles_dates_with_no_template_slots(): void
    {
        $template = CoachWeeklyTemplate::factory()->create([
            'coach_id' => $this->coach->id,
            'is_active' => true,
        ]);

        // Template only has a Monday slot
        TemplateSlot::factory()->create([
            'template_id' => $template->id,
            'day_of_week' => 1, // Monday
        ]);

        $service = new ScheduleGenerationService;
        // Generate for Tuesday (June 23, 2026 is Tuesday)
        $tuesday = Carbon::parse('2026-06-23');
        $slots = $service->generateForDate($this->coach->id, $tuesday);

        $this->assertEmpty($slots);
        $this->assertEquals(0, ScheduleSlot::where('coach_id', $this->coach->id)->where('slot_date', '2026-06-23')->count());
    }
}
