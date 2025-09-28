<?php

namespace App\Http\Resources\Bookings;

use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'booking_id'    => $this->id,
            'hotel_id'      => $this->hotel->id ?? null,
            'hotel_name'    => $this->hotel->name ?? null,
            'user_name'     => $this->user_name ?? $this->user->name ?? null,
            'user_email'    => $this->user_email ?? $this->user->email ?? null,
            'user_phone'    => $this->user_phone ?? $this->user->phone ?? null,
            'room_id'       => $this->room->id ?? null,
            'room_number'   => $this->room->room_number ?? null,
            'room_type'     => $this->room->room_type ?? null,
            'check_in'      => $this->check_in_date,
            'check_out'     => $this->check_out_date,
            'total_amount'  => $this->total_amount,
            'status'        => $this->status,
        ];
    }
}
