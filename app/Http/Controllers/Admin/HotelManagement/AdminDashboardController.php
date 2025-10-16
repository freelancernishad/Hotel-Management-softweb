<?php

namespace App\Http\Controllers\Admin\HotelManagement;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\HotelManagement\Hotel;
use App\Models\HotelManagement\Booking;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function overview()
    {
        // Hotel statistics
        $totalHotels = Hotel::count();
        $activeHotels = Hotel::active()->count();
        $inactiveHotels = Hotel::inactive()->count();

        // Booking statistics
        $totalBookings = Booking::count();
        $confirmedBookings = Booking::confirmed()->count();
        $pendingBookings = Booking::pending()->count();
        $cancelledBookings = Booking::cancelled()->count();
        $completedBookings = Booking::completed()->count();

        // Revenue
        $totalRevenue = Booking::confirmed()->sum('total_amount');
        $monthlyRevenue = Booking::confirmed()
            ->selectRaw('MONTH(check_in_date) as month, SUM(total_amount) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // User statistics
        $totalUsers = User::count();
        $repeatCustomers = Booking::select('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(id) > 1')
            ->count();

        // Cancellation rate
        $cancellationRate = $totalBookings > 0
            ? round(($cancelledBookings / $totalBookings) * 100, 2)
            : 0;

        // Average booking value
        $averageBookingValue = $confirmedBookings > 0
            ? round($totalRevenue / $confirmedBookings, 2)
            : 0;

        return response()->json([
            'hotels' => [
                'total' => $totalHotels,
                'active' => $activeHotels,
                'inactive' => $inactiveHotels,
            ],
            'bookings' => [
                'total' => $totalBookings,
                'confirmed' => $confirmedBookings,
                'pending' => $pendingBookings,
                'cancelled' => $cancelledBookings,
                'completed' => $completedBookings,
                'cancellation_rate' => $cancellationRate,
            ],
            'revenue' => [
                'total' => $totalRevenue,
                'average_booking_value' => $averageBookingValue,
                'monthly' => $monthlyRevenue,
            ],
            'users' => [
                'total' => $totalUsers,
                'repeat_customers' => $repeatCustomers,
            ],
        ]);
    }
}
