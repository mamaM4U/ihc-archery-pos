<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'supplier_id',
        'status',
        'notes',
        'total',
        'discount',
        'grand_total',
        'created_by',
        'finalized_by',
        'finalized_at',
    ];

    protected $casts = [
        'id' => 'integer',
        'supplier_id' => 'integer',
        'total' => 'integer',
        'discount' => 'integer',
        'grand_total' => 'integer',
        'created_by' => 'integer',
        'finalized_by' => 'integer',
        'finalized_at' => 'datetime',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function finalizer()
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }
}
