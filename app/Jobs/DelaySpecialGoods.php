<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\Business\DelaySpecialGoods as DelaySpecialGoodsModel;

class DelaySpecialGoods implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    private $id;


    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $goods = DelaySpecialGoodsModel::where('id' , $this->id)->first();
        if(!empty($goods))
        {
            $data = $goods->toArray();
            $now = date('Y-m-d H:i:s');
            $data['created_at'] = $now;
            $data['updated_at'] = $now;
            unset($data['start_time']);
            DB::table('delay_special_goods')->where('id' , $this->id)->delete();
            DB::table('special_goods')->insert($data);
            $key = "helloo:business:goods:service:special:".$goods->goods_id;
            Redis::hmset($key , array(
                'special_price'=>$data['special_price'],
                'free_delivery'=>$data['free_delivery'],
                'packaging_cost'=>$data['packaging_cost'],
                'deadline'=>$data['deadline'],
                'status'=>$data['status'],
            ));
            Redis::EXPIREAT($key , strtotime($data['deadline']));
        }
    }

}
