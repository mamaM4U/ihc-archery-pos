<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CoachWeeklyTemplate extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'coach_id',
        'template_name',
        'booking_open_days',
        'is_active',
        'notes',
    ];

    /**
     * The default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
        'booking_open_days' => 7,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'booking_open_days' => 'integer',
        ];
    }

    /**
     * Get the coach that owns this template.
     *
     * @return BelongsTo<User, $this>
     */
    public function coach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coach_id');
    }

    /**
     * Get the slots for this template.
     *
     * @return HasMany<TemplateSlot, $this>
     */
    public function templateSlots(): HasMany
    {
        return $this->hasMany(TemplateSlot::class, 'template_id');
    }
}
