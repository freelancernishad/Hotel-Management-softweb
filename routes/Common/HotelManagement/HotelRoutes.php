<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\HotelManagement\HotelController;
use App\Http\Controllers\Admin\HotelManagement\BookingController;
use App\Http\Controllers\Common\HotelManagement\HotelSearchController;



 Route::get('hotels', [HotelController::class, 'index']);


Route::get('/hotels/search', [HotelSearchController::class, 'search']);
Route::get('get/hotels/details/{id}', [HotelSearchController::class, 'show']);


Route::post('hotel/bookings', [BookingController::class, 'store']);
Route::post('hotel/multiple/bookings', [BookingController::class, 'storeMultiple']);
Route::get('hotel/bookings/{id}', [BookingController::class, 'show']);
