<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class Bitrix24Company implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $shop;
    private $type;

    public function __construct($shop , $type)
    {
        $this->shop = $shop;
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
        if(stripos($type, 'store') !==false)
        {
            $this->store($this->shop);
        }elseif (stripos($type, 'update') !==false)
        {
            $this->update($this->shop);
        }
    }

    private function store($shop)
    {
        $bitrix = DB::table('bitrix_shops')->where('user_id' , $shop->user_id)->first();
        if(empty($bitrix))
        {
            $bx24 = app("bitrix24");
            $phone = DB::table('users_phones')->where('user_id' , $shop->user_id)->first();
            $id = $bx24->addCompany(array(
                "TITLE"=>$shop->user_name,
                "COMPANY_TYPE"=>"CLIENT",
                "INDUSTRY"=>"OTHER",
                "CURRENCY_ID"=>"ETB",
                "PHONE"=>array(
                    array(
                        "VALUE"=>empty($phone)?'':$phone->user_phone_country.$phone->user_phone, "VALUE_TYPE"=>"WORK"
                    )
                ),
                "ADDRESS"=>$shop->user_address,
                "UF_CRM_1629181078"=>$shop->user_nick_name
            ));
            $sectionId = $bx24->request('crm.productsection.add' , array(
                'fields'=>array(
                    'CATALOG_ID'=>0,
                    'NAME'=>$shop->user_nick_name,
                    'XML_ID'=>$shop->user_id,
                )
            ));
            DB::table('bitrix_shops')->insert(array(
                'user_id'=>$shop->user_id,
                'extension_id'=>$id,
                'section_id'=>$sectionId,
            ));
        }else{
            Log::info('shop_exists_in_bitrix' , array(
                $shop->user_id
            ));
        }

    }

    private function update($shop)
    {
        $bitrix = DB::table('bitrix_shops')->where('user_id' , $shop->user_id)->first();
        $bx24 = app("bitrix24");
        if(empty($bitrix))
        {
            Log::info('shop_not_exists_in_bitrix');
        }else{
            $bx24->updateCompany(
                $bitrix->extension_id,
                array(
                    "TITLE"=>$shop->user_name,
                    "ADDRESS"=>$shop->user_address,
                    "UF_CRM_1629181078"=>$shop->user_nick_name
                )
            );
        }
    }

    public function __call($name , $params)
    {
        Log::info('__call' , array(
            'name'=>$name,
            'params'=>$params,
        ));
    }

}
