<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TemplateSlot extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'template_id',
        'day_of_week',
        'session_name',
        'start_time',
        'end_time',
        'location',
        'max_capacity',
        'duration_minutes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'max_capacity' => 'integer',
            'duration_minutes' => 'integer',
        ];
    }

    /**
     * Get the weekly template that owns this slot.
     *
     * @return BelongsTo<CoachWeeklyTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(CoachWeeklyTemplate::class, 'template_id');
    }

    /**
     * Get the generated schedule slots for this template slot.
     *
     * @return HasMany<ScheduleSlot, $this>
     */
    public function scheduleSlots(): HasMany
    {
        return $this->hasMany(ScheduleSlot::class, 'template_slot_id');
    }
}
