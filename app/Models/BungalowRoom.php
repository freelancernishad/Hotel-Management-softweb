<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BungalowRoom extends Model
{
    use HasFactory;
    protected $fillable = ['bungalow_id', 'room_number'];

    public function bungalow()
    {
        return $this->belongsTo(Bungalow::class);
    }

    public function bookings()
    {
        return $this->hasMany(BungalowBooking::class);
    }
}
