<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MembershipPlan extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'category',
        'price',
        'duration_days',
        'session_quota',
        'description',
        'equipment_provided',
        'family_members',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'duration_days' => 'integer',
            'session_quota' => 'integer',
            'equipment_provided' => 'boolean',
            'family_members' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the customer memberships for this plan.
     */
    public function customerMemberships(): HasMany
    {
        return $this->hasMany(CustomerMembership::class);
    }

    /**
     * Scope a query to only include active plans.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
