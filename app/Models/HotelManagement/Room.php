<?php

namespace App\Models\HotelManagement;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'hotel_id',
        'room_number',
        'room_type',
        'price_per_night',
        'capacity',
        'description',
        'image',
        'availability'
    ];

    protected $casts = [
        'availability' => 'boolean',
    ];

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function isAvailable($checkInDate, $checkOutDate)
    {
        return !$this->bookings()
            ->where('status', 'confirmed')
            ->where(function ($query) use ($checkInDate, $checkOutDate) {
                $query->whereBetween('check_in_date', [$checkInDate, $checkOutDate])
                    ->orWhereBetween('check_out_date', [$checkInDate, $checkOutDate])
                    ->orWhere(function ($query) use ($checkInDate, $checkOutDate) {
                        $query->where('check_in_date', '<=', $checkInDate)
                            ->where('check_out_date', '>=', $checkOutDate);
                    });
            })
            ->exists();
    }

    public function calculateTotalPrice($checkInDate, $checkOutDate)
    {
        $checkIn = \Carbon\Carbon::parse($checkInDate);
        $checkOut = \Carbon\Carbon::parse($checkOutDate);
        $nights = $checkIn->diffInDays($checkOut);

        return $this->price_per_night * $nights;
    }
}
