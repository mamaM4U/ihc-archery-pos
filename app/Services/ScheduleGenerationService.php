<?php

namespace App\Services;

use App\Models\CoachWeeklyTemplate;
use App\Models\ScheduleSlot;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ScheduleGenerationService
{
    /**
     * Generate schedule slots for a coach on a specific date if they don't already exist.
     *
     * @return Collection<int, ScheduleSlot>
     */
    public function generateForDate(int $coachId, Carbon $date): Collection
    {
        $dateStr = $date->toDateString();

        // 1. Check if schedule slots already exist for this date and coach.
        $existing = ScheduleSlot::where('coach_id', $coachId)
            ->where('slot_date', $dateStr)
            ->get();

        if ($existing->isNotEmpty()) {
            return $existing;
        }

        // 2. Find the active weekly template for the coach
        $activeTemplate = CoachWeeklyTemplate::where('coach_id', $coachId)
            ->where('is_active', true)
            ->with('templateSlots')
            ->first();

        if (! $activeTemplate) {
            return collect();
        }

        // Carbon's dayOfWeek returns 0 (Sunday) to 6 (Saturday)
        $dayOfWeek = $date->dayOfWeek;

        // Filter templates slots that match this day of the week
        $matchingSlots = $activeTemplate->templateSlots
            ->where('day_of_week', $dayOfWeek);

        if ($matchingSlots->isEmpty()) {
            return collect();
        }

        // Use a transaction to prevent race conditions or partial inserts
        DB::transaction(function () use ($coachId, $dateStr, $matchingSlots) {
            foreach ($matchingSlots as $slot) {
                // Double check if a concurrent request already created it
                $exists = ScheduleSlot::where('coach_id', $coachId)
                    ->where('slot_date', $dateStr)
                    ->where('start_time', $slot->start_time)
                    ->where('end_time', $slot->end_time)
                    ->exists();

                if (! $exists) {
                    ScheduleSlot::create([
                        'coach_id' => $coachId,
                        'template_slot_id' => $slot->id,
                        'slot_date' => $dateStr,
                        'session_name' => $slot->session_name,
                        'start_time' => $slot->start_time,
                        'end_time' => $slot->end_time,
                        'location' => $slot->location,
                        'max_capacity' => $slot->max_capacity,
                        'current_bookings' => 0,
                        'status' => 'available',
                    ]);
                }
            }
        });

        // Retrieve and return all schedule slots (newly created) for this date
        return ScheduleSlot::where('coach_id', $coachId)
            ->where('slot_date', $dateStr)
            ->orderBy('start_time')
            ->get();
    }

    /**
     * Generate schedule slots for a coach for a range of dates.
     *
     * @return Collection<int, ScheduleSlot>
     */
    public function generateForDateRange(int $coachId, Carbon $startDate, Carbon $endDate): Collection
    {
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $this->generateForDate($coachId, $currentDate);
            $currentDate->addDay();
        }

        return ScheduleSlot::where('coach_id', $coachId)
            ->whereBetween('slot_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderBy('slot_date')
            ->orderBy('start_time')
            ->get();
    }
}
