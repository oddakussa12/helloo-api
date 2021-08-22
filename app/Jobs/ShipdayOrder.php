<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ShipdayOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    private $order;


    public function __construct($order)
    {
        $this->order = $order;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $order = $this->order;
        $address = DB::table('orders_addresses')->where('order_id' , $order['order_id'])->first();
        $shop = User::where('user_id' , $order['shop_id'])->first();
        $phone = DB::table('users_phones')->where('user_id' , $order['shop_id'])->first();
        $detail = \json_decode($order['detail'] , true);
        $orderItem = array();
        $str = "";
        foreach ($detail as $d)
        {
            if($d['discounted_price']>=0)
            {
                $str .= $d['name'] . "Unit price after discount is" . (string)$d['discounted_price'] .' &&';
            }
            array_push($orderItem  , array(
                'name'=>$d['name'],
                'quantity'=>$d['goodsNumber'],
                'unitPrice'=>$d['price'],
                'detail'=>$str,
            ));
        }

        $data = [
            "orderNumber" => $order['order_id'],
            "customerName" => $order['user_name'],
            "customerAddress" => $order['user_address'],
            "customerEmail" => "",
            "customerPhoneNumber" => $order['user_contact'],
            "restaurantName" => $shop->user_nick_name,
            "restaurantAddress" => $shop->user_address,
            "restaurantPhoneNumber" => empty($phone)?'':$phone->user_phone_country.$phone->user_phone,
//            "expectedDeliveryDate"=>'',
//            "expectedPickupTime"=>'',
//            "expectedDeliveryTime"=>'',
//                "pickupLatitude" => 0,
//                "pickupLongitude" => 0,
//            "deliveryLatitude" => $address->user_longitude??0,
//            "deliveryLongitude" => $address->user_latitude??0,
            "orderItem" => $orderItem,
            "tips" => 0,
            "tax" => 0,
            "discountAmount" => $order['total_price'],
            "deliveryFee" => $order['delivery_coast'],
            "totalOrderCost" => $order['discounted_price'],
            "deliveryInstruction" => "fast",
            "orderSource" => "",
            "additionalId" => (string)$order['order_id'],
            "clientRestaurantId" => (string)$order['shop_id'],
            "paymentMethod"=>'cash',
//            "creditCardType"=>'',
            "creditCardId"=>0,
        ];
        if(!empty(deliveryLatitude))
        {
            $data['deliveryLongitude'] = $address->user_longitude??0;
            $data['deliveryLatitude'] = $address->user_latitude??0;
        }
        $this->curl($data);

    }

    public function curl($data)
    {
        Log::info(__FUNCTION__.'_data' , $data);
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.shipday.com/orders',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>\json_encode($data),
            CURLOPT_HTTPHEADER => array(
                'Authorization: Basic '.config('common.ship_day_token'),
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        Log::info(__FUNCTION__.'_result' , array('$response'=>$response));

        $order = \json_decode($response , true);
        if(isset($order['orderId']))
        {
            DB::table('orders')->where('order_id' , $data['additionalId'])->update(array(
                'ship_id'=>$response['orderId']
            ));
        }
    }

}
