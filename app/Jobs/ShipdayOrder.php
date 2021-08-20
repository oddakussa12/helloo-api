<?php

namespace App\Jobs;

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


    private $orders;
    private $addresses;


    public function __construct($orders , $addresses)
    {
        $this->orders = $orders;
        $this->addresses = $addresses;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $orders = $this->orders;
        $addresses = collect($this->addresses);
        $shopIds = collect($orders)->pluck('shop_id')->unique()->toArray();
        $phones = DB::table('users_phones')->whereIn('user_id' , $shopIds)->get();
        foreach ($orders as $order)
        {
            $phone = $phones->where('user_id' , $order['shop_id'])->first();
            $address = $addresses->where('order_id' , $order['order_id'])->first();
            $detail = $order['detail'];
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
                "restaurantName" => $order['shop']->user_nick_name,
                "restaurantAddress" => $order['shop']->user_address,
                "restaurantPhoneNumber" => empty($phone)?'':$phone->user_phone_country.$phone->user_phone,
//            "expectedDeliveryDate"=>'',
//            "expectedPickupTime"=>'',
//            "expectedDeliveryTime"=>'',
//                "pickupLatitude" => 0,
//                "pickupLongitude" => 0,
                "deliveryLatitude" => $address['user_latitude'],
                "deliveryLongitude" => $address['user_longitude'],
                "orderItem" => $orderItem,
                "tips" => 0,
                "tax" => 0,
                "discountAmount" => $order['total_price'],
                "deliveryFee" => $order['delivery_coast'],
                "totalOrderCost" => $order['discounted_price'],
                "deliveryInstruction" => "fast",
                "orderSource" => "",
                "additionalId" => "",
                "clientRestaurantId" => "",
                "paymentMethod"=>'cash',
//            "creditCardType"=>'',
                "creditCardId"=>0,
            ];
            $this->curl($data);
        }

    }

    public function curl($data)
    {
        Log::info('shipday_data' , $data);
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
        Log::info('Shipday_result' , array('$response'=>$response));
    }

}
