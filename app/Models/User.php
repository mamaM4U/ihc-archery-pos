<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'phone',
        'role',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Accessor for avatar URL.
     */
    protected function avatar(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (! $value) {
                    return null;
                }

                if (
                    str_starts_with($value, 'http://') ||
                    str_starts_with($value, 'https://') ||
                    str_starts_with($value, '/storage/')
                ) {
                    return $value;
                }

                return asset('storage/'.ltrim($value, '/'));
            }
        );
    }

    /**
     *  get all permissions users
     */
    public function getPermissions()
    {
        return $this->getAllPermissions()->mapWithKeys(function ($permission) {
            return [
                $permission['name'] => true,
            ];
        });
    }

    /**
     * check role isSuperAdmin
     */
    public function isSuperAdmin()
    {
        return $this->hasRole('admin');
    }

    /**
     * Guardians for this user (if this user is a member).
     */
    public function guardians()
    {
        return $this->belongsToMany(User::class, 'user_relationships', 'member_id', 'guardian_id')
            ->withPivot('can_approve_booking')
            ->withTimestamps();
    }

    /**
     * Members/dependents managed by this user (if this user is a guardian).
     */
    public function members()
    {
        return $this->belongsToMany(User::class, 'user_relationships', 'guardian_id', 'member_id')
            ->withPivot('can_approve_booking')
            ->withTimestamps();
    }

    /**
     * Coaches assigned to this user (if this user is a member).
     */
    public function coaches()
    {
        return $this->belongsToMany(User::class, 'coach_members', 'member_id', 'coach_id')
            ->withTimestamps();
    }

    /**
     * Members assigned to this coach (if this user is a coach).
     */
    public function coachMembers()
    {
        return $this->belongsToMany(User::class, 'coach_members', 'coach_id', 'member_id')
            ->withTimestamps();
    }

    /**
     * Get the weekly templates for this coach.
     */
    public function weeklyTemplates(): HasMany
    {
        return $this->hasMany(CoachWeeklyTemplate::class, 'coach_id');
    }

    /**
     * Get the schedule slots for this coach.
     */
    public function scheduleSlots(): HasMany
    {
        return $this->hasMany(ScheduleSlot::class, 'coach_id');
    }

    /**
     * Check if user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Check if user is a coach.
     */
    public function isCoach(): bool
    {
        return $this->hasRole('coach');
    }

    /**
     * Check if user is a guardian.
     */
    public function isGuardian(): bool
    {
        return $this->hasRole('guardian');
    }

    /**
     * Check if user is a member.
     */
    public function isMember(): bool
    {
        return $this->hasRole('member');
    }
}
