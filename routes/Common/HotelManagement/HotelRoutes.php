<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Common\HotelManagement\HotelSearchController;


Route::get('/hotels/search', [HotelSearchController::class, 'search']);
Route::get('get/hotels/details/{id}', [HotelSearchController::class, 'show']);
