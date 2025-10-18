<?php

namespace App\Http\Controllers\Admin\HotelManagement;

use App\Models\Bungalow;
use App\Models\BungalowRoom;
use Illuminate\Http\Request;
use App\Models\BungalowBooking;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse; // JSON রেসপন্সের জন্য

class BungalowBookingController extends Controller
{
    /**
     * সব ডাকবাংলো এবং তাদের রুমের তালিকা দেখানোর জন্য API Endpoint
     * GET /api/bungalows
     */
    public function getBungalows(): JsonResponse
    {
        $bungalows = Bungalow::with('rooms')->get();
        return response()->json([
            'status' => 'success',
            'data' => $bungalows
        ], 200);
    }

    /**
     * নতুন বুকিং তৈরি করার জন্য API Endpoint
     * POST /api/bookings
     */
    public function createBooking(Request $request): JsonResponse
    {
        // ফর্ম ভ্যালিডেশন
        $validator = Validator::make($request->all(), [
            'room_id'        => 'required|exists:bungalow_rooms,id',
            'guest_name'     => 'required|string|max:255',
            'guest_address'  => 'required|string',
            'mobile_number'  => 'required|string|max:15',
            'check_in_date'  => 'required|date|after_or_equal:today',
            'check_out_date' => 'required|date|after:check_in_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();

        // ✅ শুধুমাত্র active বুকিং (pending, confirmed, checked_in) স্ট্যাটাসে থাকা বুকিং চেক করবে
        $isBooked = BungalowBooking::where('room_id', $validated['room_id'])
            ->whereIn('status', ['pending', 'confirmed', 'checked_in']) // <-- Added status filter
            ->where(function ($query) use ($validated) {
                $query->whereBetween('check_in_date', [$validated['check_in_date'], $validated['check_out_date']])
                    ->orWhereBetween('check_out_date', [$validated['check_in_date'], $validated['check_out_date']])
                    ->orWhere(function ($query) use ($validated) {
                        $query->where('check_in_date', '<', $validated['check_in_date'])
                                ->where('check_out_date', '>', $validated['check_out_date']);
                    });
            })
            ->exists();

        if ($isBooked) {
            return response()->json([
                'status' => 'error',
                'message' => 'দুঃখিত, নির্বাচিত তারিখে এই কক্ষটি ইতিমধ্যে বুকিং রয়েছে।'
            ], 409); // 409 Conflict
        }

        // ✅ নতুন বুকিং তৈরি (ডিফল্ট স্ট্যাটাস 'pending')
        $booking = BungalowBooking::create(array_merge($validated, [
            'status' => 'pending',
        ]));

        return response()->json([
            'status'  => 'success',
            'message' => 'বুকিং সফলভাবে সম্পন্ন হয়েছে!',
            'data'    => $booking
        ], 201); // 201 Created
    }


    /**
     * সব বুকিংয়ের তালিকা ড্যাশবোর্ডের জন্য দেখানোর API Endpoint
     * GET /api/bookings
     */
    public function getBookings(): JsonResponse
    {
        // আজকের পরে র সব বুকিং দেখানো হচ্ছে
        $bookings = BungalowBooking::with('room.bungalow')
            ->where('check_out_date', '>=', today())
            ->orderBy('check_in_date', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $bookings
        ], 200);
    }



    function deleteBooking($id) {
        $booking = BungalowBooking::find($id);

        if (!$booking) {
            return response()->json([
                'status' => 'error',
                'message' => 'বুকিং খুঁজে পাওয়া যায়নি।'
            ], 404);
        }

        $booking->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'বুকিং সফলভাবে মুছে ফেলা হয়েছে।'
        ], 200);
    }


      /**
     * Update booking status
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,confirmed,checked_in,checked_out,cancelled',
        ]);

        $booking = BungalowBooking::find($id);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found.',
            ], 404);
        }

        $booking->status = $request->status;
        $booking->save();

        return response()->json([
            'success' => true,
            'message' => 'Booking status updated successfully.',
            'data' => $booking,
        ]);
    }
}
