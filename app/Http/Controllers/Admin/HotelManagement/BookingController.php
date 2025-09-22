<?php

namespace App\Http\Controllers\Admin\HotelManagement;

use App\Http\Controllers\Controller;
use App\Models\HotelManagement\Booking;
use App\Models\HotelManagement\Room;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class BookingController extends Controller
{
    /**
     * Create a new booking
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id'        => 'nullable|exists:users,id',
            'hotel_id'       => 'required|exists:hotels,id',
            'room_id'        => 'required|exists:rooms,id',
            'check_in_date'  => 'required|date|after_or_equal:today',
            'check_out_date' => 'required|date|after:check_in_date',
            'special_requests' => 'nullable|string',
            'status'         => 'nullable|in:pending,confirmed,cancelled,completed'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $room = Room::findOrFail($request->room_id);

        // Check if room is available for the given dates
        if (!$room->isAvailable($request->check_in_date, $request->check_out_date)) {
            return response()->json([
                'success' => false,
                'message' => 'Room is not available for the selected dates'
            ], 400);
        }

        // Prepare booking data
        $bookingData = [
            'user_id'        => $request->user_id,
            'hotel_id'       => $request->hotel_id,
            'room_id'        => $request->room_id,
            'check_in_date'  => $request->check_in_date,
            'check_out_date' => $request->check_out_date,
            'special_requests' => $request->special_requests,
            'status'         => $request->status ?? Booking::STATUS_PENDING,
        ];

        // If user exists, save user info snapshot
        if ($request->user_id) {
            $user = User::find($request->user_id);
            $bookingData['user_name']  = $user->fullName ?? $user->name ?? null;
            $bookingData['user_email'] = $user->email ?? null;
            $bookingData['user_phone'] = $user->phone ?? null;
        }

        // Calculate total amount
        $bookingData['total_amount'] = $room->calculateTotalPrice($request->check_in_date, $request->check_out_date);

        $booking = Booking::create($bookingData);

        return response()->json([
            'success' => true,
            'booking' => $booking
        ], 201);
    }



    public function storeMultiple(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id'        => 'nullable|exists:users,id',
            'hotel_id'       => 'required|exists:hotels,id',
            'room_ids'       => 'required|array|min:1',
            'room_ids.*'     => 'exists:rooms,id',
            'check_in_date'  => 'required|date|after_or_equal:today',
            'check_out_date' => 'required|date|after:check_in_date',
            'special_requests' => 'nullable|string',
            'status'         => 'nullable|in:pending,confirmed,cancelled,completed'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user_id ? User::find($request->user_id) : null;
        $createdBookings = [];

        foreach ($request->room_ids as $roomId) {
            $room = Room::findOrFail($roomId);

            if (!$room->isAvailable($request->check_in_date, $request->check_out_date)) {
                return response()->json([
                    'success' => false,
                    'message' => "Room {$room->id} is not available for the selected dates"
                ], 400);
            }

            $bookingData = [
                'user_id'        => $request->user_id,
                'hotel_id'       => $request->hotel_id,
                'room_id'        => $roomId,
                'check_in_date'  => $request->check_in_date,
                'check_out_date' => $request->check_out_date,
                'special_requests' => $request->special_requests,
                'status'         => $request->status ?? Booking::STATUS_PENDING,
                'total_amount'   => $room->calculateTotalPrice($request->check_in_date, $request->check_out_date),
                'user_name'      => $user->fullName ?? $user->name ?? null,
                'user_email'     => $user->email ?? null,
                'user_phone'     => $user->phone ?? null,
            ];

            $createdBookings[] = Booking::create($bookingData);
        }

        return response()->json([
            'success' => true,
            'bookings' => $createdBookings
        ], 201);
    }


    /**
     * Show booking details by ID
     */
    public function show($id)
    {
        $booking = Booking::with(['room', 'hotel', 'user'])->find($id);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'booking' => $booking
        ]);
    }
}
