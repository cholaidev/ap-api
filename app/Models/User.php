<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
    ];

    protected $hidden = ['password'];

    // 🔗 Relationships
    public function customers()
    {
        return $this->hasMany(Customer::class, 'assigned_to');
    }

    public function deliveries()
    {
        return $this->hasMany(Delivery::class, 'staff_id');
    }
}
