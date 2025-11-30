<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;
use Stripe\StripeObject;
use App\Helpers\Handlers\PlanSubscriptionHandler;

class Ekpay
{



public static function ekpayToken($trnx_id=123456789,$trns_info=[],$cust_info=[],$urls){


    $url = config('AKPAY_IPN_URL') ?? url('ekpay/ipn');


   $req_timestamp = date('Y-m-d H:i:s');


 $AKPAY_MER_REG_ID = 'tetulia_test';
$AKPAY_MER_PASS_KEY = 'TetuLiA@tsT19';

    if($AKPAY_MER_REG_ID=='tetulia_test'){
        $Apiurl = 'https://sandbox.ekpay.gov.bd/ekpaypg/v1';
        $whitelistip = '1.1.1.1';
    }else{
        $Apiurl = 'https://pg.ekpay.gov.bd/ekpaypg/v1';
        $whitelistip = config('WHITE_LIST_IP');
    }


   $post = [
      'mer_info' => [
         "mer_reg_id" => $AKPAY_MER_REG_ID,
         "mer_pas_key" => $AKPAY_MER_PASS_KEY
      ],
      "req_timestamp" => "$req_timestamp GMT+6",
      "feed_uri" => [
         "c_uri" => $urls['c_uri'],
         "f_uri" => $urls['f_uri'],
         "s_uri" => $urls['s_uri']
      ],
      "cust_info" => $cust_info,
      "trns_info" =>$trns_info,
      "ipn_info" => [
         "ipn_channel" => "3",
         "ipn_email" => "freelancernishad123@gmail.com",
         "ipn_uri" => "$url/api/ekpay/ipn"
      ],
      "mac_addr" => "$whitelistip"
   ];

   // 148.163.122.80
   $post = json_encode($post);
   Log::info($post);

   $ch = curl_init($Apiurl.'/merchant-api');
   curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
   curl_setopt($ch, CURLOPT_POST, true);
   curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
   curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
   curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
   $response = curl_exec($ch);
   curl_close($ch);

/*      echo '<pre>';
   print_r($response); */

   Log::info($response);
//    return $response;

     $response = json_decode($response);

   if (isset($response->secure_token) && !empty($response->secure_token)) {
    return "$Apiurl?sToken={$response->secure_token}&trnsID={$trnx_id}";
} else {

    return $response;
}


//  return    'https://sandbox.ekpay.gov.bd/ekpaypg/v1?sToken=eyJhbGciOiJIUzUxMiJ9.eyJzdWIiOiJla3BheWNvcmUiLCJhdXRoIjoiUk9MRV9NRVJDSEFOVCIsImV4cCI6MTU0NTMyMjcxMn0.lqjBuvtqyUbhy4pteKa0IaqpjYQoEDjjnJWSFwcv0Ho2JJHN-8xqr8Q7r-tIJUy_dLajS2XbmrR6lBGrlGFYhQ&trnsID=1234'


//   return "https://sandbox.ekpay.gov.bd/ekpaypg/v1?sToken=$sToken&trnsID=$trnx_id";

}




}
