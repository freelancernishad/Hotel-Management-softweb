<?php

namespace App\Http\Controllers\Common\HotelManagement;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\HotelManagement\Hotel;
use Illuminate\Support\Facades\Validator;

class HotelSearchController extends Controller
{
    /**
     * Search hotels by date, room type, and number of rooms
     */
    public function search(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'check_in_date'  => 'required|date|after_or_equal:today',
            'check_out_date' => 'required|date|after:check_in_date',
            'room_type'      => 'nullable|string',
            'rooms_count'    => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
            'success' => false,
            'errors'  => $validator->errors()
            ], 422);
        }

        $checkIn  = $request->check_in_date;
        $checkOut = $request->check_out_date;
        $roomType = $request->room_type;
        $roomsCount = $request->rooms_count ?? 1;

        // Query hotels with available rooms
        $hotels = Hotel::with(['rooms' => function ($q) use ($checkIn, $checkOut, $roomType) {
            $q->where('availability', true);

            if ($roomType) {
                $q->where('room_type', $roomType);
            }

            // exclude booked rooms
            $q->whereDoesntHave('bookings', function ($query) use ($checkIn, $checkOut) {
                $query->where('status', 'confirmed')
                    ->where(function ($q) use ($checkIn, $checkOut) {
                        $q->whereBetween('check_in_date', [$checkIn, $checkOut])
                          ->orWhereBetween('check_out_date', [$checkIn, $checkOut])
                          ->orWhere(function ($q) use ($checkIn, $checkOut) {
                              $q->where('check_in_date', '<=', $checkIn)
                                ->where('check_out_date', '>=', $checkOut);
                          });
                    });
            });
        }])->get();

        // Filter hotels which have at least requested rooms available
        $hotels = $hotels->filter(function ($hotel) use ($roomsCount) {
            return $hotel->rooms->count() >= $roomsCount;
        })->values();

        // Format response
        $results = $hotels->map(function ($hotel) {
            return [
                'id'          => $hotel->id,
                'name'        => $hotel->name,
                'location'    => $hotel->location,
                'description' => $hotel->description,
                'contact_number' => $hotel->contact_number,
                'email'       => $hotel->email,
                'image'      => $hotel->image,
                'is_active'   => $hotel->is_active,
                'rooms_available' => $hotel->rooms->count(),
                'rooms'       => $hotel->rooms->map(function ($room) {
                    return [
                        'id'           => $room->id,
                        'room_number'  => $room->room_number,
                        'room_type'    => $room->room_type,
                        'price_per_night' => $room->price_per_night,
                        'capacity'     => $room->capacity,
                        'availability' => $room->availability,
                        'description'  => $room->description,
                        'image'        => $room->image,
                    ];
                }),
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $results
        ]);
    }


    /**
     * Show hotel details by ID
     */
    public function show(Request $request, $id)
    {



        if(!$request->check_in_date || !$request->check_out_date){
            $hotel = Hotel::with('rooms')->find($id);
            return response()->json([
                'success' => true,
                'hotel' => $hotel
            ]);
        }
       



        $validator = Validator::make($request->all(), [
            'check_in_date'  => 'required|date|after_or_equal:today',
            'check_out_date' => 'required|date|after:check_in_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
            'success' => false,
            'errors'  => $validator->errors()
            ], 422);
        }

        $checkIn  = $request->check_in_date;
        $checkOut = $request->check_out_date;



        $hotel = Hotel::with('rooms')->find($id);

        if (!$hotel) {
            return response()->json([
                'success' => false,
                'message' => 'Hotel not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'hotel' => [
                'id'          => $hotel->id,
                'name'        => $hotel->name,
                'description' => $hotel->description,
                'location'    => $hotel->location,
                'contact_number' => $hotel->contact_number,
                'email'       => $hotel->email,
                'manager_id'  => $hotel->manager_id,
                'is_active'   => $hotel->is_active,
                'image'       => $hotel->image,
                'rooms'       => $hotel->getAvailableRooms($checkIn, $checkOut)->map(function ($room) {
                    return [
                        'id'            => $room->id,
                        'room_number'   => $room->room_number,
                        'room_type'     => $room->room_type,
                        'price_per_night' => $room->price_per_night,
                        'capacity'      => $room->capacity,
                        'availability'  => $room->availability,
                        'description'   => $room->description,
                        'image'         => $room->image,
                    ];
                }),
            ]
        ]);
    }






}
