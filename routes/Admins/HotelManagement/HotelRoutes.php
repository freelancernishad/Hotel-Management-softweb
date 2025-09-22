<?php
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\AuthenticateAdmin;
use App\Http\Controllers\Admin\HotelManagement\HotelController;
use App\Http\Controllers\Admin\HotelManagement\BookingController;

Route::prefix('admin')->group(function () {
    Route::middleware(AuthenticateAdmin::class)->group(function () {
        Route::prefix('/hotels')->group(function () {
            Route::get('/', [HotelController::class, 'index']);
            Route::post('/', [HotelController::class, 'store']);
            Route::get('{id}', [HotelController::class, 'show']);
            Route::post('{hotelId}/rooms', [HotelController::class, 'addRooms']);
            Route::get('{hotelId}/available-rooms', [HotelController::class, 'availableRooms']);
        });
    });
});



Route::post('hotel/bookings', [BookingController::class, 'store']);
Route::post('hotel/multiple/bookings', [BookingController::class, 'storeMultiple']);
Route::get('hotel/bookings/{id}', [BookingController::class, 'show']);
