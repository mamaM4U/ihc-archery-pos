<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerMembership extends Model
{
    use HasFactory;

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'remaining_sessions',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'customer_id',
        'membership_plan_id',
        'transaction_id',
        'start_date',
        'end_date',
        'session_quota',
        'session_used',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'session_quota' => 'integer',
            'session_used' => 'integer',
        ];
    }

    /**
     * Get the customer that owns this membership.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the membership plan for this membership.
     */
    public function membershipPlan(): BelongsTo
    {
        return $this->belongsTo(MembershipPlan::class);
    }

    /**
     * Get the transaction associated with this membership.
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Get the session usages for this membership.
     */
    public function sessionUsages(): HasMany
    {
        return $this->hasMany(SessionUsage::class);
    }

    /**
     * Get the remaining sessions for this membership.
     */
    public function getRemainingSessionsAttribute(): int
    {
        return max(0, $this->session_quota - $this->session_used);
    }

    /**
     * Get the remaining days until this membership expires.
     */
    public function getRemainingDaysAttribute(): int
    {
        return max(0, now()->startOfDay()->diffInDays($this->end_date, false));
    }

    /**
     * Determine if this membership is expiring soon (within 7 days).
     */
    public function getIsExpiringSoonAttribute(): bool
    {
        return $this->status === 'active' && $this->remaining_days <= 7;
    }

    /**
     * Scope a query to only include active memberships.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include memberships for a specific customer.
     */
    public function scopeForCustomer(Builder $query, int $customerId): Builder
    {
        return $query->where('customer_id', $customerId);
    }
}
