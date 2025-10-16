<?php

namespace App\Http\Controllers\Admin\HotelManagement;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\HotelManagement\Room;
use Illuminate\Support\Facades\Auth;
use App\Models\HotelManagement\Hotel;
use Illuminate\Support\Facades\Validator;

class HotelController extends Controller
{
    // Hotel list
    public function index()
    {
        $hotels = Hotel::with('rooms', 'manager')->get();
        return response()->json([
            'success' => true,
            'data' => $hotels
        ]);
    }

    // Create a new hotel
   public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'           => 'required|string|max:255',
            'description'    => 'nullable|string',
            'location'       => 'required|string',
            'contact_number' => 'required|string',
            'email'          => 'required|email',
            'image'          => 'nullable|string',
            'manager_id'     => 'nullable|exists:users,id',
            'is_active'      => 'boolean',
            'username'       => 'required|string|unique:hotels,username',
            'password'       => 'required|string|min:8',
            'rooms'          => 'nullable|array',
            'features'       => 'nullable|array',
            'features.*'     => 'string|max:255',
            'gallery'        => 'nullable|array',
            'gallery.*'      => 'string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $hotelData = $request->only([
            'name', 'description', 'location', 'contact_number', 'email', 'manager_id', 'image', 'username', 'features', 'gallery'
        ]);

        // ✅ Laravel 10+ automatically hashes due to model $casts
        $hotelData['password'] = $request->password;

        // ✅ Default behavior
        $hotelData['is_active'] = false;

        // ✅ If the authenticated user is admin, make it active
        if (auth('admin')->check()) {
            $hotelData['is_active'] = true;
        }

        $hotel = Hotel::create($hotelData);

        $createdRooms = [];
        if ($request->has('rooms')) {
            $createdRooms = $this->createRooms($request->rooms, $hotel->id, true);
        }

        return response()->json([
            'success' => true,
            'hotel' => $hotel,
            'rooms' => $createdRooms
        ], 201);
    }



    // Update hotel
    public function update(Request $request, $id)
    {
        $hotel = Hotel::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name'           => 'nullable|string|max:255',
            'description'    => 'nullable|string',
            'location'       => 'nullable|string',
            'contact_number' => 'nullable|string',
            'email'          => 'nullable|email',
            'image'          => 'nullable|string',
            'manager_id'     => 'nullable|exists:users,id',
            'is_active'      => 'boolean',
            'username'       => 'nullable|string|unique:hotels,username,' . $hotel->id,
            'password'       => 'nullable|string|min:8',
            'features'       => 'nullable|array',
            'features.*'     => 'string|max:255',
            'gallery'        => 'nullable|array', // ✅ new
            'gallery.*'      => 'string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $hotelData = $request->only([
            'name', 'description', 'location', 'contact_number', 'email',
            'manager_id', 'is_active', 'image', 'username', 'features', 'gallery'
        ]);

        // Update password if provided
        if ($request->filled('password')) {
            $hotelData['password'] = $request->password;
        }

        $hotel->update($hotelData);

        return response()->json([
            'success' => true,
            'hotel' => $hotel
        ]);
    }

    /**
     * Add multiple rooms to a hotel
     * $roomsData = array of rooms
     * $hotelId = ID of hotel
     * $returnOnlyRooms = if true, just return created rooms without JSON response
     */
    protected function createRooms(array $roomsData, $hotelId, $returnOnlyRooms = false)
    {
        $createdRooms = [];
        foreach ($roomsData as $roomData) {
            $roomData['hotel_id'] = $hotelId;
            $createdRooms[] = Room::create($roomData);
        }

        return $returnOnlyRooms ? $createdRooms : response()->json([
            'success' => true,
            'rooms' => $createdRooms
        ], 201);
    }


    // Add multiple rooms under a hotel
    public function addRooms(Request $request, $hotelId = null)
    {
        $validator = Validator::make($request->all(), [
            'rooms' => 'required|array|min:1',
            'rooms.*.room_number'     => 'required|string',
            'rooms.*.room_type'       => 'required|string',
            'rooms.*.price_per_night' => 'required|numeric|min:0',
            'rooms.*.capacity'        => 'required|integer|min:1',
            'rooms.*.description'     => 'nullable|string',
            'rooms.*.image'           => 'nullable|string', // image URL
            'rooms.*.availability'    => 'boolean',
            'rooms.*.features'        => 'nullable|array', // ✅ new
            'rooms.*.features.*'      => 'string|max:255',
            'rooms.*.gallery'        => 'nullable|array', // ✅ new
            'rooms.*.gallery.*'      => 'string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // যদি hotelId না দেওয়া হয়, auth hotel থেকে নিন
        if (!$hotelId) {
            $hotel = Auth::guard('hotel')->user();
            $hotelId = $hotel->id;
        } else {
            $hotel = Hotel::findOrFail($hotelId);
        }

        $createdRooms = $this->createRooms($request->rooms, $hotelId, true);

        return response()->json([
            'success' => true,
            'message' => 'Rooms created successfully.',
            'rooms' => $createdRooms
        ], 201);
    }



    /**
     * Get all rooms of a hotel
     */
    public function getRooms(Request $request, $hotelId = null)
    {
        // যদি hotelId না দেওয়া হয়, auth hotel থেকে নিন
        if (!$hotelId) {
            $hotel = Auth::guard('hotel')->user();
            $hotelId = $hotel->id;
        } else {
            $hotel = Hotel::findOrFail($hotelId);
        }

        $rooms = $hotel->rooms()->get();

        return response()->json([
            'success' => true,
            'hotel_id' => $hotelId,
            'rooms' => $rooms
        ], 200);
    }



    // Show hotel details including rooms
    public function show($id)
    {
        $hotel = Hotel::with('rooms', 'manager')->findOrFail($id);
        return response()->json($hotel);
    }

    // destroy hotel details including rooms
    public function destroy($id)
    {
        $hotel = Hotel::with('rooms', 'manager')->findOrFail($id);
        $hotel->delete();

        return response()->json([
            'success' => true,
            'message' => 'Hotel deleted successfully.',
        ]);
    }

    // Available rooms for a date range
    public function availableRooms(Request $request, $hotelId)
    {
        $request->validate([
            'check_in_date' => 'required|date',
            'check_out_date' => 'required|date|after:check_in_date',
        ]);

        $hotel = Hotel::findOrFail($hotelId);

        $availableRooms = $hotel->availableRooms($request->check_in_date, $request->check_out_date);

        return response()->json([
            'success' => true,
            'available_rooms' => $availableRooms
        ]);
    }




    // Update a room
    public function updateRoom(Request $request, $roomId)
    {
        $room = Room::findOrFail($roomId);

        $validator = Validator::make($request->all(), [
            'room_number'     => 'nullable|string',
            'room_type'       => 'nullable|string',
            'price_per_night' => 'nullable|numeric|min:0',
            'capacity'        => 'nullable|integer|min:1',
            'description'     => 'nullable|string',
            'image'           => 'nullable|string',
            'availability'    => 'boolean',
            'features'        => 'nullable|array', // ✅ new
            'features.*'      => 'string|max:255',
            'gallery'        => 'nullable|array', // ✅ new
            'gallery.*'      => 'string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $roomData = $request->only([
            'room_number', 'room_type', 'price_per_night', 'capacity',
            'description', 'image', 'availability', 'features','gallery'
        ]);

        $room->update($roomData);

        return response()->json([
            'success' => true,
            'room' => $room
        ]);
    }

    // Delete a room
    public function deleteRoom($roomId)
    {
        $room = Room::findOrFail($roomId);
        $room->delete();

        return response()->json([
            'success' => true,
            'message' => 'Room deleted successfully.'
        ]);
    }

    // Get details of a single room
    public function getRoomDetails($roomId)
    {
        $room = Room::with('hotel', 'bookings', 'reviews')->findOrFail($roomId);

        return response()->json([
            'success' => true,
            'room' => $room
        ]);
    }





}
