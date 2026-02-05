<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'house_name',
        'location',

        // 🐄🐃 Milk quantities
        'cow_milk_quantity',
        'buffalo_milk_quantity',

        // status
        'is_active',
        'cancelled_at',
    ];
}

