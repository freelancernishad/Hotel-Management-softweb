<?php

namespace App\Http\Controllers\Admin\HotelManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HotelManagement\Hotel;
use App\Models\HotelManagement\Room;
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
        'rooms'          => 'nullable|array'
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $hotelData = $request->only([
        'name', 'description', 'location', 'contact_number', 'email', 'manager_id', 'is_active', 'image'
    ]);

    $hotel = Hotel::create($hotelData);

    $createdRooms = [];
    if ($request->has('rooms')) {
        // Reuse createRooms function
        $createdRooms = $this->createRooms($request->rooms, $hotel->id, true);
    }

    return response()->json([
        'success' => true,
        'hotel' => $hotel,
        'rooms' => $createdRooms
    ], 201);
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
public function addRooms(Request $request, $hotelId)
{
    $validator = Validator::make($request->all(), [
        'rooms' => 'required|array|min:1',
        'rooms.*.room_number'     => 'required|string',
        'rooms.*.room_type'       => 'required|string',
        'rooms.*.price_per_night' => 'required|numeric|min:0',
        'rooms.*.capacity'        => 'required|integer|min:1',
        'rooms.*.description'     => 'nullable|string',
        'rooms.*.image'           => 'nullable|url', // image URL
        'rooms.*.availability'    => 'boolean',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $hotel = Hotel::findOrFail($hotelId);

     $createdRooms = $this->createRooms($request->rooms, $hotel->id, true);
    return response()->json([
        'success' => true,
        'rooms' => $createdRooms
    ], 201);


}


    // Show hotel details including rooms
    public function show($id)
    {
        $hotel = Hotel::with('rooms', 'manager')->findOrFail($id);
        return response()->json($hotel);
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
}
