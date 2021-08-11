<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class OrderSms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    private $orderInfo;
    private $type;
    private $orderSmsDomain;

    public function __construct($orderInfo , $type = 'single')
    {
        $this->orderInfo = $orderInfo;
        $this->type = $type;
        $this->orderSmsDomain = config('common.order_sms_domain');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if($this->type=='single')
        {
            $data = collect($this->orderInfo)->except('goods_id', 'owner', 'updated_at')->toArray();
            $country = DB::table('users_countries')->where('user_id', $data['user_id'])->first();
            if (!empty($data['goods_id'])) {
                $goods = DB::table('goods')->where('id', $data['goods_id'])->first();
            }
            $params['customerPhone']   = $data['user_contact'];
            $params['customerAddress'] = $data['user_address'];
            $params['customerOrder']   = !empty($goods) ? $goods->goods_name : null;
            $params['customerCountry'] = !empty($country) && in_array($country->country, ['et', 'tl']) ? $country->country : null;
            $this->curl($params);
        }else{
            $data = $this->orderInfo;
            foreach ($data as $d)
            {
                $country = DB::table('users_countries')->where('user_id', $d['shop_id'])->first();
                $params = array(
                    'customerPhone'=>$d['user_contact'],
                    'customerAddress'=>$d['user_address'],
                    'customerOrder'=>$d['order_id'],
                    'customerCountry'=>!empty($country) && in_array($country->country, ['et', 'tl']) ? $country->country : null
                );
                $this->curl($params);
            }
        }

    }

    public function curl($params)
    {
        $curl = curl_init();
        $url = 'http://beu-notification-api.eba-f6wpgpmf.us-west-2.elasticbeanstalk.com/v1/sms/outbound';
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>\json_encode($params),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'hello: '.$this->orderSmsDomain
            ),
        ));

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl,CURLINFO_HTTP_CODE);
        curl_close($curl);
        Log::info(__CLASS__.'_result' , array('$response'=>$response , '$httpCode'=>$httpCode , '$params'=>$params));
    }

}
