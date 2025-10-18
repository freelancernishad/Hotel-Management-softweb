<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BungalowBooking extends Model
{
    use HasFactory;
    protected $fillable = [
        'room_id',
        'guest_name',
        'guest_address',
        'mobile_number',
        'check_in_date',
        'check_out_date',
        'status',
    ];

    protected $dates = ['check_in_date', 'check_out_date'];

    public function room()
    {
        return $this->belongsTo(BungalowRoom::class);
    }
}
