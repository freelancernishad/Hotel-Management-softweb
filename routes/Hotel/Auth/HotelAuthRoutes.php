<?php

use App\Http\Controllers\Admin\HotelManagement\HotelController;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\AuthenticateHotel;
use App\Http\Controllers\Auth\Hotel\HotelAuthController;


Route::prefix('auth/hotel')->group(function () {
    Route::post('login', [HotelAuthController::class, 'login'])->name('hotel.login');

    Route::middleware(AuthenticateHotel::class)->group(function () { // Applying hotel middleware
        Route::post('logout', [HotelAuthController::class, 'logout']);
        Route::get('me', [HotelAuthController::class, 'me']);
        Route::post('/change-password', [HotelAuthController::class, 'changePassword']);
        Route::get('check-token', [HotelAuthController::class, 'checkToken']);
    });
});


    Route::middleware(AuthenticateHotel::class)->group(function () {
        Route::post('hotel/profile', [HotelAuthController::class, 'updateProfile']);
        Route::post('hotel/create/rooms', [HotelController::class, 'addRooms']);
        Route::get('hotel/get/rooms', [HotelController::class, 'getRooms']);
    });
