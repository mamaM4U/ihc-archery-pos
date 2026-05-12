<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'return_number',
        'purchase_id',
        'supplier_id',
        'resolution_type',
        'status',
        'total_return_amount',
        'refund_amount',
        'credited_amount',
        'notes',
        'created_by',
        'completed_by',
        'completed_at',
    ];

    protected $casts = [
        'id' => 'integer',
        'purchase_id' => 'integer',
        'supplier_id' => 'integer',
        'total_return_amount' => 'integer',
        'refund_amount' => 'integer',
        'credited_amount' => 'integer',
        'created_by' => 'integer',
        'completed_by' => 'integer',
        'completed_at' => 'datetime',
    ];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function completer()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }
}
