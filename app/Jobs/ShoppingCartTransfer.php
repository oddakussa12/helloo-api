<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ShoppingCartTransfer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $userId;

    private $goodsIds;


    public function __construct($userId , $goodsIds)
    {
        $this->userId = $userId;
        $this->goodsIds = (array)$goodsIds;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        DB::table('shopping_carts')->where('user_id' , $this->userId)->whereIn('goods_id' , $this->goodsIds)->delete();
    }

}
