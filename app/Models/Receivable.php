<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Receivable extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'transaction_id',
        'invoice',
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

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function payments()
    {
        return $this->hasMany(ReceivablePayment::class);
    }

    public function getRemainingAttribute(): float
    {
        return max(0, ($this->total ?? 0) - ($this->paid ?? 0));
    }
}
