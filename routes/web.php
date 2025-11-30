<?php

use App\Helpers\Mpdf\MpdfHelpers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use App\Models\HotelManagement\Booking;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\Common\SystemSettings\SystemSettingController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/db-check', function () {
    try {
        DB::connection()->getPdo(); // DB connection check
        $databaseName = DB::connection()->getDatabaseName();
        return "✅ Database connected successfully! Database name: " . $databaseName;
    } catch (\Exception $e) {
        return "❌ Database connection failed: " . $e->getMessage();
    }
});

Route::get('/run-migrate', function() {
    Artisan::call('migrate', ['--force' => true]);
    return "Migrations completed!";
});



// For web routes
Route::get('/clear-cache', [SystemSettingController::class, 'clearCache']);





Route::get('booking/invoice/{booking_reference}',function ($booking_reference){

    $booking = Booking::with('hotel', 'room')->where('booking_reference', $booking_reference)->first();
    // return response()->json($booking);
    if(!$booking){
        return response()->json(["error" => "Booking not found"], 404);
    }

        $htmlView = view('invoice.booking', compact('booking'))->render();



        $header = null; // Add HTML for header if required
        $footer = null; // Add HTML for footer if required
        $filename = "invoice" . $booking->booking_reference . ".pdf";
        return  MpdfHelpers::generatePdf($htmlView, $header, $footer, $filename);

});
