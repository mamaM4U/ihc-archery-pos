<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseReturnItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_return_id',
        'purchase_item_id',
        'product_id',
        'qty_return',
        'buy_price',
        'subtotal',
        'return_reason',
    ];

    protected $casts = [
        'id' => 'integer',
        'purchase_return_id' => 'integer',
        'purchase_item_id' => 'integer',
        'product_id' => 'integer',
        'qty_return' => 'integer',
        'buy_price' => 'integer',
        'subtotal' => 'integer',
    ];

    public function purchaseReturn()
    {
        return $this->belongsTo(PurchaseReturn::class);
    }

    public function purchaseItem()
    {
        return $this->belongsTo(PurchaseItem::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
