<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'staff_id',
        'product_id',
        'quantity',
        'unit_price',
        'total',
        'date',
        'status',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // 💰 Dynamic profit from this delivery
    public function getProfitAttribute()
    {
        if (!$this->product) return 0;
        return $this->quantity * ($this->unit_price - $this->product->cost_price);
    }
}
