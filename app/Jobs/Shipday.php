<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class Shipday implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    private $orderNumber;
    private $customerName;
    private $customerAddress;
    private $customerPhoneNumber;
    private $restaurantName;
    private $restaurantAddress;
    private $restaurantPhoneNumber;
    private $orderItem;
    private $totalOrderCost;
    private $tax;


    public function __construct($orderNumber , $customerName , $customerAddress ,  $customerPhoneNumber , $restaurantName , $restaurantAddress , $restaurantPhoneNumber , $orderItem , $totalOrderCost , $tax)
    {
        $this->orderNumber = $orderNumber;
        $this->customerName = $customerName;
        $this->customerAddress = $customerAddress;
        $this->customerPhoneNumber = $customerPhoneNumber;
        $this->restaurantName = $restaurantName;
        $this->restaurantAddress = $restaurantAddress;
        $this->restaurantPhoneNumber = $restaurantPhoneNumber;
        $this->orderItem = $orderItem;
        $this->totalOrderCost = $totalOrderCost;
        $this->tax = $tax;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $data = [
            "orderNumber" => $this->orderNumber,
            "customerName" => $this->customerName,
            "customerAddress" => $this->customerAddress,
            "customerEmail" => "",
            "customerPhoneNumber" => $this->customerPhoneNumber,
            "restaurantName" => $this->restaurantName,
            "restaurantAddress" => $this->restaurantAddress,
            "restaurantPhoneNumber" => $this->restaurantPhoneNumber,
            "pickupLatitude" => 0,
            "pickupLongitude" => 0,
            "deliveryLatitude" => 0,
            "deliveryLongitude" => 0,
            "orderItem" => $this->orderItem,
            "totalOrderCost" => $this->totalOrderCost,
            "tax" => $this->tax,
            "tips" => "0",
            "deliveryFee" => "0",
            "deliveryInstruction" => "fast",
            "additionalId" => ""
        ];
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
                'Authorization: Basic upquBv2pED.UqKHGwFMNUzpSUxg2oRB',
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        Log::info('Shipday_result' , array('$response'=>$response));
    }

}
