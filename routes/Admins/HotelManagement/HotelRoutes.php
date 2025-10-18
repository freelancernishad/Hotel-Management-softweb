<?php
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\AuthenticateAdmin;
use App\Http\Middleware\AuthenticateHotel;
use App\Http\Controllers\Admin\HotelManagement\HotelController;
use App\Http\Controllers\Admin\HotelManagement\BookingController;
use App\Http\Controllers\Admin\HotelManagement\AdminDashboardController;
use App\Http\Controllers\Admin\HotelManagement\BungalowBookingController;

Route::prefix('admin')->group(function () {
    Route::middleware(AuthenticateAdmin::class)->group(function () {
        Route::prefix('/hotels')->group(function () {
            Route::get('/', [HotelController::class, 'index']);
            Route::post('/', [HotelController::class, 'store']);
            Route::put('/{id}', [HotelController::class, 'update']);
            Route::get('{id}', [HotelController::class, 'show']);
            Route::delete('{id}', [HotelController::class, 'destroy']);
            Route::post('{hotelId}/rooms', [HotelController::class, 'addRooms']);

            Route::put('update/room/{roomId}', [HotelController::class, 'updateRoom']);
            Route::delete('delete/room/{roomId}', [HotelController::class, 'deleteRoom']);
            Route::get('room/{roomId}', [HotelController::class, 'getRoomDetails']);

            Route::patch('{hotelId}/status', [HotelController::class, 'updateStatus']);


            Route::get('{hotelId}/available-rooms', [HotelController::class, 'availableRooms']);
        });

         Route::get('get/bookings/lists', [BookingController::class, 'index']);




         // ডাকবাংলোর তালিকা পাওয়ার জন্য
        Route::get('/bungalows', [BungalowBookingController::class, 'getBungalows']);

        // বুকিং সম্পর্কিত রাউট
        Route::get('bungalows/bookings', [BungalowBookingController::class, 'getBookings']);
        Route::post('bungalows/bookings', [BungalowBookingController::class, 'createBooking']);
        Route::delete('bungalows/bookings/{id}', [BungalowBookingController::class, 'deleteBooking']);
        Route::put('bungalows/bookings/{id}/status', [BungalowBookingController::class, 'updateStatus']);



    Route::get('hotel/overview', [AdminDashboardController::class, 'overview']);


    });
});




            Route::post('hotel/registration', [HotelController::class, 'store']);



