<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserRelationship extends Model
{
    protected $table = 'user_relationships';

    protected $fillable = [
        'guardian_id',
        'member_id',
        'can_approve_booking',
    ];

    protected function casts(): array
    {
        return [
            'can_approve_booking' => 'boolean',
        ];
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guardian_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(User::class, 'member_id');
    }
}
