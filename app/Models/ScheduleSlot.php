<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleSlot extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'coach_id',
        'template_slot_id',
        'slot_date',
        'session_name',
        'start_time',
        'end_time',
        'location',
        'max_capacity',
        'current_bookings',
        'status',
    ];

    /**
     * The default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'available',
        'current_bookings' => 0,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'slot_date' => 'date',
            'max_capacity' => 'integer',
            'current_bookings' => 'integer',
        ];
    }

    /**
     * Get the coach who owns this schedule slot.
     *
     * @return BelongsTo<User, $this>
     */
    public function coach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coach_id');
    }

    /**
     * Get the template slot this schedule slot was generated from.
     *
     * @return BelongsTo<TemplateSlot, $this>
     */
    public function templateSlot(): BelongsTo
    {
        return $this->belongsTo(TemplateSlot::class, 'template_slot_id');
    }
}
