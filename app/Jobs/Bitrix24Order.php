<?php

namespace App\Jobs;

use App\Bitrix24\Bitrix24API;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class Bitrix24Order implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $data;

    private $type;

    public function __construct($data , $type)
    {
        $this->data = $data;
        $this->type = $type;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $type = $this->type;
        if(strpos($type , 'store')!==false)
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
                return 0;
            });
            $discountedPrice = collect($detail)->sum(function ($de){
                $discountedPrice = $de['discounted_price']<=0?0:$de['discounted_price'];
                return $de['goodsNumber']*$discountedPrice;
            });
            $discounted = '';
            if($d['discount_type']=='discount')
            {
                $discounted = $d['discount'];
            }elseif ($d['discount_type']=='reduction')
            {
                $discounted = $d['reduction'];
            }
            $dealId = $bx24->addDeal([
                "ID"=>$d['id'],
                "TITLE"=>'New order from bot , Created at '. date("Y-m-d H:i:s"),
                "STAGE_ID"=>'NEW',
                "IS_NEW"=>'true',
                "CURRENCY_ID"=>'BIRR',
                "COMPANY_ID"=>'1',
                "CONTACT_ID"=>$contactId,
                "BEGINDATE"=>$d['created_at'],
                "COMMENTS"=>$d['currency'],
//                "ASSIGNED_BY_ID"=>'11',
                "DATE_CREATE"=>$d['created_at'],
                "DATE_MODIFY"=>$d['created_at'],
                "SOURCE_ID"=>'web',
                "SOURCE_DESCRIPTION"=>'web',
                "UF_CRM_1628733276016"=>$d['order_price'],
                "UF_CRM_1628733612424"=>$specialPrice,
                "UF_CRM_1628733649125"=>$discountedPrice,
                "UF_CRM_1628733813318"=>$discounted,
                "UF_CRM_1628733763094"=>$d['total_price'],
                "UF_CRM_1628734060152"=>$d['packaging_cost'],
//                "UF_CRM_1628734075984"=>$d['packaging_cost'],
//                "UF_CRM_1628734746554"=>'', //订单原价
//                "UF_CRM_1629098340599"=>'', //包装费
//                "UF_CRM_1628756015643"=>'', //ship day
//                "UF_CRM_1629103387129"=>'', //收了多少钱
//                "UF_CRM_1629103354670"=>'', //是否收到钱

            ]);
            Log::info('bitrix_store_product' , array(
                $dealId
            ));
        }

    }

    private function update($data)
    {
        Log::info('$data' , $data);
    }


    private function __call($name , $params)
    {
        Log::info('__call' , array(
            'name'=>$name,
            'params'=>$params,
        ));
    }

}
