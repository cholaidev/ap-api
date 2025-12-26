<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supply extends Model
{
    protected $fillable = [
        'employee_id',
        'total_quantity',
        'total_amount',
        'is_supplied',
        'supply_date',
        'is_assigned_only'
    ];

    protected $casts = [
        'is_supplied' => 'boolean',
        'total_quantity' => 'float',
        'total_amount' => 'float',
    ];

   public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }

  
    public function items()
    {
        return $this->hasMany(SupplyItem::class, 'supply_id', 'id');
    }
}
