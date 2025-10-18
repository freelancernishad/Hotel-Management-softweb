<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use App\Models\HotelManagement\Booking;
use App\Models\HotelManagement\Hotel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HotelDashboardController extends Controller
{
    public function overview(Request $request)
    {
        // ধরে নিচ্ছি authenticated hotel guard ব্যবহার করা হচ্ছে
        $hotel = auth('hotel')->user();

        if (!$hotel) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Rooms data
        $totalRooms = $hotel->rooms()->count();
        $availableRooms = $hotel->rooms()->where('availability', true)->count();
        $occupiedRooms = $hotel->rooms()->where('availability', false)->count();

        // Booking data
        $totalBookings = $hotel->bookings()->count();
        $confirmedBookings = $hotel->bookings()->confirmed()->count();
        $pendingBookings = $hotel->bookings()->pending()->count();
        $cancelledBookings = $hotel->bookings()->cancelled()->count();
        $completedBookings = $hotel->bookings()->completed()->count();

        // Revenue
        $totalRevenue = $hotel->bookings()->confirmed()->sum('total_amount');
        $monthlyRevenue = $hotel->bookings()->confirmed()
            ->selectRaw('MONTH(check_in_date) as month, SUM(total_amount) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Guest statistics
        $totalGuests = $hotel->bookings()->confirmed()->sum('number_of_guests');
        $averageStay = $hotel->bookings()->confirmed()
            ->select(DB::raw('AVG(DATEDIFF(check_out_date, check_in_date)) as avg_stay'))
            ->value('avg_stay');

        // Performance metrics
        $occupancyRate = $totalRooms > 0
            ? round(($occupiedRooms / $totalRooms) * 100, 2)
            : 0;

        $bookingConversionRate = $totalBookings > 0
            ? round(($confirmedBookings / $totalBookings) * 100, 2)
            : 0;

        $cancellationRate = $totalBookings > 0
            ? round(($cancelledBookings / $totalBookings) * 100, 2)
            : 0;

        return response()->json([
            'hotel' => [
                'name' => $hotel->name,
                'location' => $hotel->location,
                'total_rooms' => $totalRooms,
                'available_rooms' => $availableRooms,
                'occupied_rooms' => $occupiedRooms,
                'occupancy_rate' => $occupancyRate,
            ],
            'bookings' => [
                'total' => $totalBookings,
                'confirmed' => $confirmedBookings,
                'pending' => $pendingBookings,
                'cancelled' => $cancelledBookings,
                'completed' => $completedBookings,
                'conversion_rate' => $bookingConversionRate,
                'cancellation_rate' => $cancellationRate,
            ],
            'revenue' => [
                'total' => $totalRevenue,
                'monthly' => $monthlyRevenue,
            ],
            'guests' => [
                'total' => $totalGuests,
                'average_stay_days' => round($averageStay, 2),
            ],
        ]);
    }
}
