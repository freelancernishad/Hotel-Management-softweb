<?php

namespace App\Http\Controllers;

use App\Models\Sonod;

use App\Models\Payment;

use App\Models\Uniouninfo;
use Illuminate\Http\Request;
use App\Helpers\SmsNocHelper;
use App\Models\HoldingBokeya;

use App\Models\UddoktaSearch;
use App\Models\Tenders\Tender;
use App\Models\EkpayCredential;
use App\Models\HotelManagement\Booking;
use App\Models\Tenders\TenderList;
use Illuminate\Support\Facades\Log;
use App\Models\Tenders\TanderInvoice;

class EkpayController extends Controller
{
    public function ipn(Request $request)
    {
        // Log the incoming request data for debugging
        $data = $request->all();
        Log::info('Received IPN data: ' . json_encode($data));

        // Validate that the data is not empty
        if (empty($data)) {
            Log::error('IPN data is empty');
            return response()->json(['error' => 'IPN data is empty'], 400);
        }

        // Validate that required keys exist in the data
        $requiredKeys = ['cust_info', 'trnx_info', 'msg_code', 'pi_det_info'];
        foreach ($requiredKeys as $key) {
            if (!isset($data[$key])) {
                Log::error('Missing key in IPN data: ' . $key);
                return response()->json(['error' => 'Missing key: ' . $key], 400);
            }
        }

        // Proceed with processing the data
        $booking = Booking::find($data['cust_info']['cust_id']);

        // Prepare the data to be inserted or updated
        $Insertdata = [];

        // Process based on the message code
        if ($data['msg_code'] == '1020') {

            $paymeent_method = $data['pi_det_info']['pi_gateway'];
         
            $booking->update(['status' => 'completed', 'payment_method' => $paymeent_method]);


        } else {
            // Payment failed
            $booking->update(['status' => 'failed', 'payment_method' => 'Failed']);
            $Insertdata = ['status' => 'Failed'];
        }



        return response()->json(['message' => 'IPN processed successfully'], 200);

    }






}
