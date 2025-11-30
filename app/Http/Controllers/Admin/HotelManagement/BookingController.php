<?php

namespace App\Http\Controllers\Admin\HotelManagement;

use Carbon\Carbon;
use App\Models\User;
use App\Helpers\Ekpay;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\HotelManagement\Room;
use Illuminate\Support\Facades\Auth;
use App\Models\HotelManagement\Booking;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\Bookings\BookingResource;
use App\Http\Resources\Bookings\BookingCollection;

class BookingController extends Controller
{
    /**
     * Create a new booking
     */
public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'name'              => 'nullable|string|max:255',
        'email'             => 'nullable|email|max:255',
        'phone'             => 'nullable|string|max:20',
        'user_id'           => 'nullable|exists:users,id',
        'hotel_id'          => 'required|exists:hotels,id',
        'room_id'           => 'required|exists:rooms,id',
        'check_in_date'     => 'required|date|after_or_equal:today',
        'check_out_date'    => 'required|date|after:check_in_date',
        'special_requests'  => 'nullable|string',
        'user_address'      => 'nullable|string|max:500',
        'number_of_guests'  => 'nullable|integer|min:1',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $room = Room::findOrFail($request->room_id);

    // Check if room is available
    if (!$room->isAvailable($request->check_in_date, $request->check_out_date)) {
        return response()->json([
            'success' => false,
            'message' => 'Room is not available for the selected dates'
        ], 400);
    }

    // Handle user
    if ($request->user_id) {
        $user = User::find($request->user_id);
    } elseif (!empty($request->email) && $user = User::where('email', $request->email)->first()) {
        // Existing user found by email — optionally update missing fields
        $dirty = false;
        if ($request->name && empty($user->name)) {
            $user->name = $request->name;
            $dirty = true;
        }

        if ($dirty) {
            $user->save();
        }
    } else {
        // Create new user if not exists
        $user = User::create([
            'name'     => $request->name ?? 'Guest User',
            'email'    => $request->email ?? 'guest'.time().'@example.com',
            'password' => bcrypt('password123'),
        ]);
    }

    // Prepare booking data
    $bookingData = [
        'user_id'            => $user->id,
        'hotel_id'           => $request->hotel_id,
        'room_id'            => $request->room_id,
        'check_in_date'      => $request->check_in_date,
        'check_out_date'     => $request->check_out_date,
        'special_requests'   => $request->special_requests,
        'user_address'       => $request->user_address,
        'number_of_guests'   => $request->number_of_guests,
        'booking_reference'  => 'BK-' . strtoupper(uniqid()),
        'status'             => Booking::STATUS_PENDING,
        'user_name'          => $user->name,
        'user_email'         => $user->email,
        'user_phone'         => $user->phone,
        'total_amount'       => $room->calculateTotalPrice($request->check_in_date, $request->check_out_date),
    ];

    $booking = Booking::create($bookingData);

      $applicant_mobile = $user->phone;
        $total_amount = $booking->total_amount;

        $trnx_id = $booking->booking_reference;

        $cust_info = [
            "cust_email" => "",
            "cust_id" => (string) $booking->id,
            "cust_mail_addr" => "Address",
            "cust_mobo_no" => $applicant_mobile,
            "cust_name" => $user->name
        ];

        $trns_info = [
            "ord_det" => 'auto_bike',
            "ord_id" => (string) $user->id,
            "trnx_amt" => $total_amount,
            "trnx_currency" => "BDT",
            "trnx_id" => $trnx_id
        ];

        $urls = [
            'c_uri' => $request->input('c_uri'),
            'f_uri' => $request->input('f_uri'),
            's_uri' => $request->input('s_uri'),
        ];

        $redirectUrl = Ekpay::ekpayToken($trnx_id, $trns_info, $cust_info, $urls);

        $booking['redirectUrl'] = $redirectUrl;

    return response()->json([
        'success' => true,
        'booking' => $booking,
        'redirectUrl' => $redirectUrl
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
            'status'         => 'nullable|in:pending,confirmed,cancelled,completed',
            'user_address'      => 'nullable|string|max:500',
            'number_of_guests'  => 'nullable|integer|min:1',
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
                'user_address'       => $request->user_address,
                'number_of_guests'   => $request->number_of_guests,
                'booking_reference'  => 'BK-' . strtoupper(uniqid()),
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




        /**
     * List booked rooms
     * Admin sees all hotels
     * Hotel guard sees only their hotel's bookings
     */
   public function index(Request $request)
    {
        $query = Booking::query();

        // Filter by hotel_id (admin only)
        if ($request->filled('hotel_id')) {
            $query->where('hotel_id', $request->hotel_id);
        }

        // Only confirmed bookings by default (optional)
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Auth guard: hotel
        if (Auth::guard('hotel')->check()) {
            $hotelId = Auth::guard('hotel')->id();
            $query->where('hotel_id', $hotelId);
        }

        // Date range filter
        if ($request->filled('from_date') && $request->filled('to_date')) {
            $from = Carbon::parse($request->from_date)->startOfDay();
            $to   = Carbon::parse($request->to_date)->endOfDay();

            $query->where(function ($q) use ($from, $to) {
                $q->whereBetween('check_in_date', [$from, $to])
                  ->orWhereBetween('check_out_date', [$from, $to]);
            });
        }

        // Filter by room type (join with room)
        if ($request->filled('room_type')) {
            $query->whereHas('room', function ($q) use ($request) {
                $q->where('room_type', $request->room_type);
            });
        }

        // Filter by user name (partial match)
        if ($request->filled('user_name')) {
            $query->where(function ($q) use ($request) {
                $q->where('user_name', 'like', '%' . $request->user_name . '%')
                  ->orWhereHas('user', function ($uq) use ($request) {
                      $uq->where('name', 'like', '%' . $request->user_name . '%');
                  });
            });
        }

        // Eager load related models
        $bookings = $query
            ->with(['room', 'hotel', 'user'])
            ->orderBy('check_in_date', 'desc')
            ->paginate(20); // pagination added


            return new BookingCollection($bookings);


        return response()->json([
            'success' => true,
            'data' => $bookings->map(function ($b) {
                return [
                    'booking_id'    => $b->id,
                    'hotel_id'      => $b->hotel->id ?? null,
                    'hotel_name'    => $b->hotel->name ?? null,
                    'user_name'     => $b->user_name ?? $b->user->name ?? null,
                    'user_email'    => $b->user_email ?? $b->user->email ?? null,
                    'user_phone'    => $b->user_phone ?? $b->user->phone ?? null,
                    'user_address'       => $b->user_address,
                    'number_of_guests'   => $b->number_of_guests,
                    'booking_reference'  => $b->booking_reference,
                    'payment_method'     => $b->payment_method,
                    'cancellation_reason'=> $b->cancellation_reason,
                    'room_id'       => $b->room->id ?? null,
                    'room_number'   => $b->room->room_number ?? null,
                    'room_type'     => $b->room->room_type ?? null,
                    'check_in'      => $b->check_in_date,
                    'check_out'     => $b->check_out_date,
                    'total_amount'  => $b->total_amount,
                    'status'        => $b->status,
                ];
            }),
            'pagination' => [
                'current_page' => $bookings->currentPage(),
                'last_page'    => $bookings->lastPage(),
                'total'        => $bookings->total(),
            ]
        ]);
    }


    public function updateStatus(Request $request, $bookingId)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,confirmed,cancelled,completed'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $booking = Booking::find($bookingId);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found'
            ], 404);
        }

        $booking->status = $request->status;
        $booking->save();

        return response()->json([
            'success' => true,
            'message' => 'Booking status updated successfully',
            'booking' => $booking
        ]);
    }




}
