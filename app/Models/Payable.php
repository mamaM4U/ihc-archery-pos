<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payable extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'document_number',
        'total',
        'paid',
        'due_date',
        'status',
        'note',
    ];

    protected $casts = [
        'total'    => 'float',
        'paid'     => 'float',
        'due_date' => 'date',
    ];

    protected $appends = [
        'remaining',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function payments()
    {
        return $this->hasMany(PayablePayment::class);
    }

    public function getRemainingAttribute(): float
    {
        return max(0, ($this->total ?? 0) - ($this->paid ?? 0));
    }
}
