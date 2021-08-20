<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Bitrix24Product implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $goods;
    private $type;

    public function __construct($goods , $type)
    {
        $this->goods = $goods;
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
        $this->$type($this->goods);
    }

    private function store($goods)
    {
        $bx24 = app("bitrix24");
        $data = array(
                "NAME"=>$goods['name'],
                "PRICE"=>$goods['price'],
                "CURRENCY_ID"=>$goods['currency'],
                "XML_ID"=>$goods['id']
//                "DETAIL_PICTURE"=>array(
//                    "fileData"=>array(
//                        time().".jpg",
//                        chunk_split(base64_encode(file_get_contents($imgUrl)))
//                    )
//                )
        );
        $result = $bx24->addProduct($data);
        DB::table('goods')->where('id' , $goods['id'])->update(array(
            'extension_id'=>$result
        ));
        Log::info('bitrix_store_product' , array(
            $result
        ));
    }

    private function update($goods)
    {
        $bx24 = app("bitrix24");
        $data = array(
                "NAME"=>$goods['name'],
                "PRICE"=>$goods['price'],
                "CURRENCY_ID"=>$goods['currency'],
        );
        $result = $bx24->updateProduct($goods['id'] , $data);
        Log::info('bitrix_update_product' , array(
            $result
        ));
    }

    public function __call($name , $params)
    {
        Log::info('__call' , array(
            'name'=>$name,
            'params'=>$params,
        ));
    }

}
