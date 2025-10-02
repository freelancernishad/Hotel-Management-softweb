<?php
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\AuthenticateAdmin;
use App\Http\Controllers\Admin\HotelManagement\HotelController;
use App\Http\Controllers\Admin\HotelManagement\BookingController;
use App\Http\Middleware\AuthenticateHotel;

Route::prefix('admin')->group(function () {
    Route::middleware(AuthenticateAdmin::class)->group(function () {
        Route::prefix('/hotels')->group(function () {
            Route::get('/', [HotelController::class, 'index']);
            Route::post('/', [HotelController::class, 'store']);
            Route::put('/{id}', [HotelController::class, 'update']);
            Route::get('{id}', [HotelController::class, 'show']);
            Route::delete('{id}', [HotelController::class, 'destroy']);
            Route::post('{hotelId}/rooms', [HotelController::class, 'addRooms']);
            Route::get('{hotelId}/available-rooms', [HotelController::class, 'availableRooms']);
        });

         Route::get('get/bookings/lists', [BookingController::class, 'index']);


    });
});







