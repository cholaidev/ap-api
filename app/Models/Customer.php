<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'phone', 'address', 'assigned_to'];

    public function staff() {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function deliveries() {
        return $this->hasMany(Delivery::class);
    }

    public function payments() {
        return $this->hasMany(Payment::class);
    }
}
