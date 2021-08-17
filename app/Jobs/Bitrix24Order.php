<?php

namespace App\Jobs;

use App\Bitrix24\Bitrix24API;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class Bitrix24Order implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public function __construct()
    {

    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $webhookURL = 'https://b24-sjy1by.bitrix24.com/rest/1/om8rjqj54pviw3l4/';
        $bx24 = new Bitrix24API($webhookURL);
        $stages = array('NEW' , 'PREPARATION', 'PREPAYMENT_INVOICE', '1' ,'2' ,'3' ,'4' ,'LOSE' ,'APOLOGY' ,'WON');
//        dd($bx24->request('crm.currency.list'));
        $stageId = array_rand($stages);
        $id = mt_rand(111111 , 999999999);
        $now = date("Y-m-d H:i:s");
        $dealId = $bx24->addDeal([
            "TITLE"=>'New order from bot , Created at '. date("Y-m-d H:i:s"),
//            "TYPE_ID"=>'',
//            "CATEGORY_ID"=>'',
            "STAGE_ID"=>'NEW',
//            "STAGE_SEMANTIC_ID"=>'',
            "IS_NEW"=>'true',
//            "IS_RECURRING"=>'',
//            "IS_RETURN_CUSTOMER"=>'',
//            "IS_REPEATED_APPROACH"=>'',
//            "PROBABILITY"=>'',
            "CURRENCY_ID"=>'BIRR',
//            "OPPORTUNITY"=>'',
//            "IS_MANUAL_OPPORTUNITY"=>'',
//            "TAX_VALUE"=>'',
            "COMPANY_ID"=>'1',
            "CONTACT_ID"=>'2',
            /*            "CONTACT_IDS"=>'',*/
//            "QUOTE_ID"=>'',
            "BEGINDATE"=>$now,
            "CLOSEDATE"=>$now,
//            "OPENED"=>'',
//            "CLOSED"=>'',
            "COMMENTS"=>'comment test',
            "ASSIGNED_BY_ID"=>'11',
//            "CREATED_BY_ID"=>'11',
//            "MODIFY_BY_ID"=>'',
            "DATE_CREATE"=>$now,
            "DATE_MODIFY"=>$now,
            "SOURCE_ID"=>'web',
            "SOURCE_DESCRIPTION"=>'web',
//            "LEAD_ID"=>'',
//            "ADDITIONAL_INFO"=>'',
//            "LOCATION_ID"=>'',
//            "ORIGINATOR_ID"=>'',
//            "ORIGIN_ID"=>'',
//            "UTM_SOURCE"=>'',
//            "UTM_MEDIUM"=>'',
//            "UTM_CAMPAIGN"=>'',
//            "UTM_CONTENT"=>'',
//            "UTM_TERM"=>'',
            "UF_CRM_1628733276016"=>'order price',
            "UF_CRM_1628733495712"=>'shop name',
            "UF_CRM_1628733612424"=>'special price',
            "UF_CRM_1628733649125"=>'discount price',
            "UF_CRM_1628733763094"=>'reduction price',
            "UF_CRM_1628733813318"=>'discounted used',
            "UF_CRM_1628733998830"=>'package',
            "UF_CRM_1628734031097"=>'is package',
            "UF_CRM_1628734060152"=>'delivery cost',
            "UF_CRM_1628734075984"=>'is delivery',
            "UF_CRM_1628734746554"=>'order price',
            "UF_CRM_1629098340599"=>'order price_1',
            "UF_CRM_1628756015643"=>'order price_1',
//            "UF_CRM_1628735337461"=>'',
//            "UF_CRM_16287560156"
        ]);
        dump($id);
        dump($stageId);
        dump($dealId);
    }

}
