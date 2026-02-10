<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplyItem extends Model
{

    protected $table = 'supply_items';

    protected $fillable = [
    'supply_id',
    'employee_id',
    'customer_id',

    // 🐄 Cow milk
    'cow_milk_quantity',
    'cow_price_per_liter',
    'cow_amount',

    // 🐃 Buffalo milk
    'buffalo_milk_quantity',
    'buffalo_price_per_liter',
    'buffalo_amount',

    // 🔢 Totals
    'total_quantity',
    'total_amount',

    // 🚚 Delivery status
    'is_delivered',
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
