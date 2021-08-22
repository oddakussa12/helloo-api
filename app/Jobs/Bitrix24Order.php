<?php

namespace App\Jobs;

use App\Models\Business\Goods;
use App\Models\Business\Order;
use Jenssegers\Agent\Agent;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Ramsey\Uuid\Uuid;

class Bitrix24Order implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $data;

    private $type;

    private $resource;

    private $platform;

    public function __construct($data , $type)
    {
        $this->data = $data;
        $this->type = $type;
        $agent = new Agent();
        if($agent->match('HellooAndroid'))
        {
            $this->resource = "android";
            $this->platform = 47;
        }elseif ($agent->match('HellooLiteAndroid'))
        {
            $this->resource = "android_lite";
            $this->platform = 49;
        }elseif ($agent->match('HellooLiteIos'))
        {
            $this->resource = "ios_lite";
            $this->platform = 51;
        }elseif ($agent->match('HellooBot'))
        {
            $this->resource = "bot";
            $this->platform = 55;
        }else{
            $this->resource = 'web';
            $this->platform = 53;
        }
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $type = $this->type;
        if(stripos($type, 'store') !==false)
        {
            $this->store($this->data);
        }
    }

    private function store($data)
    {
        $bx24 = app('bitrix24');
        $flag = false;
        $contactId = '';
        foreach ($data as $d)
        {
            if(!$flag)
            {
                $contactId = $bx24->addContact(
                    array(
                        'NAME'=>$d['user_name'],
                        'ADDRESS'=>$d['user_address'],
                        'TYPE_ID'=>"CLIENT",
                        'PHONE'=>array(
                            array(
                                'VALUE'=>$d['user_contact'],
                                'VALUE_TYPE'=>'WORK',
                            )
                        ),
                        "UF_CRM_1629187998"=>$d['user_id']
                    )
                );
                $flag = true;
            }
            $detail = \json_decode($d['detail'] , true);
            $specialPrice = collect($detail)->sum(function ($de){
                if(isset($de['specialPrice']))
                {
                    return $de['goodsNumber']*$de['specialPrice'];
                }
                return $de['goodsNumber']*$de['price'];
            });
            $discountedPrice = collect($detail)->sum(function ($de){
                $discountedPrice = $de['discounted_price']<=0?$de['price']:$de['discounted_price'];
                return $de['goodsNumber']*$discountedPrice;
            });
            $discounted = '';
            if($d['discount_type']=='discount')
            {
                $discounted = (string)$d['discount'] . "%";
            }elseif ($d['discount_type']=='reduction')
            {
                $discounted = $d['reduction'];
            }
            $company = DB::table('bitrix_shops')->where('user_id' , $d['shop_id'])->first();
            $shop = DB::table('users')->where('user_id' , $d['shop_id'])->first();
            $deal = [
                "ID"=>$d['order_id'],
                "TITLE"=>$d['order_id'],
                "STAGE_ID"=>'NEW',
                "IS_NEW"=>'true',
                "CURRENCY_ID"=>'ETB',
                "COMPANY_ID"=> $company->extension_id ?? 0,
                "CONTACT_ID"=>$contactId,
                "BEGINDATE"=>$d['created_at'],
                "COMMENTS"=>$d['currency'],
//                "ASSIGNED_BY_ID"=>'11',
                "DATE_CREATE"=>$d['created_at'],
                "DATE_MODIFY"=>$d['created_at'],
                "SOURCE_ID"=>$this->resource ,
                "SOURCE_DESCRIPTION"=>$this->resource ,


                "UF_CRM_1628733276016"=>$d['order_price'],
                "UF_CRM_1628733612424"=>$specialPrice, //Special price
                "UF_CRM_1628733649125"=>$discountedPrice, //Shop discount price
                "UF_CRM_1628733813318"=>$discounted, //Discount Used
                "UF_CRM_1628733763094"=>$d['total_price'],
                "UF_CRM_1628733998830"=>$d['packaging_cost'],//Package fee
                "UF_CRM_1628734031097"=>$d['packaging_cost']>0?0:1, //Package fee free？
                "UF_CRM_1628734060152"=>$d['delivery_coast'], //Delivery fee
                "UF_CRM_1628734075984"=>(int)$d['free_delivery'], //Delivery fee free？
                "UF_CRM_1628735337461"=>$d['promo_code'], //Promo code
                "UF_CRM_1629192007"=>$d['order_id'],//ORDER_ID
                "UF_CRM_1629271456064"=>$d['discounted_price'], //Total price Paid
                "UF_CRM_1629274022921"=>$this->platform, //Platform type
                "UF_CRM_1629461733965"=>$shop->user_nick_name, //Restaurant
            ];
            Log::info('$deal' , $deal);
            $dealId = $bx24->addDeal($deal);
            $productData = array();
            foreach ($detail as $det)
            {
                array_push($productData , array(
                    'PRODUCT_ID'=>$det['extension_id'],
                    'PRICE'=>$det['price'],
                    'QUANTITY'=>$det['goodsNumber'],
                    'DISCOUNT_TYPE_ID'=>$d['discount_type']=='reduction'?1:2,
                    'DISCOUNT_RATE'=>0,
                    'CUSTOMIZED'=>"Y",
                    'MEASURE_CODE'=>"Y",
                    'MEASURE_NAME'=>"Y",
                    'DISCOUNT_SUM'=>$det['discounted_price']<0?0:$det['discounted_price'],
                ));
            }
            $result = $bx24->setDealProductRows($dealId , $productData);
            Log::info('bitrix_store_order' , array(
                $dealId,
                $productData,
                $result
            ));
        }

    }

    private function update($data)
    {
//        $bx24 = app('bitrix24');
//        $dealId = $data['id'];
//        $order_id = $data['order_id'];
//        $operator = $data['operator'];
//        $deliveryFee = $data['delivery_fee']??'';
//        $deliveryFree = $data['delivery_free']??'';
//        $packagingFee = $data['packaging_fee']??'';
//        $packagingFree = $data['packaging_free']??'';
//        $orderData = array('operator'=>$operator);
//        if($deliveryFree=="Y")
//        {
//            $orderData['free_delivery'] =1;
//            $orderData['packaging_cost'] =0;
//        }else{
//            $orderData['free_delivery'] =0;
//            preg_match_all("/\d+/", $deliveryFee,$arr);
//            $delivery = round((float)explode('' , $arr) , 2);
//            $orderData['packaging_cost'] = $delivery;
//        }
//        $time = date('Y-m-d H:i:s');
//        $orderData['packaging_cost'] = $packagingFree=="Y"?0:round((float)$packagingFree , 2);
//        $order = Order::where('order_id' , $order_id)->firstOrFail();
//        $orderData['discounted_price'] = $order->total_price+$orderData['packaging_cost']+$orderData['packaging_cost'];
//        DB::table('orders')->where('order_id' , $order_id)->update($orderData);
//        $order = $order->toArray();
//        $order['id'] = Uuid::uuid1()->toString();
//        $order['updated_at'] = $time;
//        DB::table('orders_logs')->insert($order);
//        $dealProducts = $bx24->getDealProductRows($dealId);
//        $goods = collect($dealProducts)->pluck('QUANTITY' , 'ID')->toArray();
//        $ids = collect($dealProducts)->pluck('ID')->toArray();

//        $gs = Goods::whereIn('extension_id' , $ids)->get();
//        $gs->each(function($g) use ($goods){
//            $g->goodsNumber = $goods[$g['extension_id']];
//        });
//        $price = $gs->sum(function ($shopG){
//            return $shopG->goodsNumber*$shopG->price;
//        });
//        $promoPrice = $gs->sum(function ($shopG){
//            if($shopG->discounted_price<0)
//            {
//                return $shopG->goodsNumber*$shopG->price;
//            }
//            return $shopG->goodsNumber*$shopG->discounted_price;
//        });
//        $shopIds = $gs->pluck('user_id')->unique()->toArray();
//        if(count($shopIds)===1)
//        {
//            $bx24->getDealProductRows($dealId , $productData);
//            $gs->sum(function ($shopG) use ($goods) {
//                return $goods[$shopG['id']]*$shopG['price'];
//            });
//        }
        Log::info('$data' , $data);
    }


    public function __call($name , $params)
    {
        Log::info('__call' , array(
            'name'=>$name,
            'params'=>$params,
        ));
    }

}
