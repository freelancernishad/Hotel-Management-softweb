<?php

namespace App\Models\HotelManagement;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'room_id',
        'hotel_id',
        'check_in_date',
        'check_out_date',
        'total_amount',
        'status',
        'special_requests',
        'user_name',
        'user_email',
        'user_phone',
        'user_address',
        'number_of_guests',
        'payment_method',
        'booking_reference',
        'cancellation_reason'
    ];

    protected $dates = [
        'check_in_date',
        'check_out_date',
        'created_at',
        'updated_at'
    ];

    // Booking statuses
    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_COMPLETED = 'completed';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    // Calculate total nights of stay
    public function getTotalNightsAttribute()
    {
        return Carbon::parse($this->check_in_date)->diffInDays(Carbon::parse($this->check_out_date));
    }

    // Calculate total amount based on room price and nights
    public function calculateTotalAmount()
    {
        $nights = $this->getTotalNightsAttribute();
        return $this->room->price_per_night * $nights;
    }

    // Check if booking is active (confirmed and within date range)
    public function isActive()
    {
        $today = Carbon::today();
        return $this->status === self::STATUS_CONFIRMED &&
               $today->between($this->check_in_date, $this->check_out_date);
    }

    // Check if booking is upcoming
    public function isUpcoming()
    {
        return $this->status === self::STATUS_CONFIRMED &&
               Carbon::today()->lt($this->check_in_date);
    }

    // Check if booking is completed
    public function isCompleted()
    {
        return $this->status === self::STATUS_COMPLETED ||
               ($this->status === self::STATUS_CONFIRMED &&
               Carbon::today()->gt($this->check_out_date));
    }

    // Scope for pending bookings
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    // Scope for confirmed bookings
    public function scopeConfirmed($query)
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    // Scope for cancelled bookings
    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    // Scope for completed bookings
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    // Scope for bookings within date range
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('check_in_date', [$startDate, $endDate])
              ->orWhereBetween('check_out_date', [$startDate, $endDate])
              ->orWhere(function ($q) use ($startDate, $endDate) {
                  $q->where('check_in_date', '<=', $startDate)
                    ->where('check_out_date', '>=', $endDate);
              });
        });
    }
}
