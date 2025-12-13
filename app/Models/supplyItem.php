<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplyItem extends Model
{
    protected $fillable = [
        'supply_id',
        'customer_id',
        'milk',
        'quantity',
        'price_per_liter',
        'amount',
    ];

    protected $casts = [
        'quantity' => 'float',
        'price_per_liter' => 'float',
        'amount' => 'float',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function supply()
    {
        return $this->belongsTo(Supply::class);
    }
}
