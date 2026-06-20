<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Car extends Model
{
    protected $table      = 'rc_cars';
    protected $primaryKey = 'car_id';

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'car_id', 'car_id');
    }
}
