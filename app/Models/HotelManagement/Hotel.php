<?php

namespace App\Models\HotelManagement;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Hotel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'location',
        'contact_number',
        'email',
        'image',
        'manager_id',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function availableRooms($checkInDate, $checkOutDate)
    {
        return $this->rooms()
            ->where('availability', true)
            ->whereDoesntHave('bookings', function ($query) use ($checkInDate, $checkOutDate) {
                $query->where('status', 'confirmed')
                    ->where(function ($q) use ($checkInDate, $checkOutDate) {
                        $q->whereBetween('check_in_date', [$checkInDate, $checkOutDate])
                            ->orWhereBetween('check_out_date', [$checkInDate, $checkOutDate])
                            ->orWhere(function ($q) use ($checkInDate, $checkOutDate) {
                                $q->where('check_in_date', '<=', $checkInDate)
                                    ->where('check_out_date', '>=', $checkOutDate);
                            });
                    });
            })
            ->get();
    }
}
