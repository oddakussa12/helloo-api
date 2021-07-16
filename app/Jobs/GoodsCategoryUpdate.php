<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;

class GoodsCategoryUpdate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $categoryIds;
    private $userId;

    public function __construct(array $categoryIds , $userId)
    {
        $this->categoryIds = $categoryIds;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        foreach ($this->categoryIds as $categoryId)
        {
            $count = DB::table('categories_goods')->where('category_id' , $categoryId)->where('status' , 1)->count();
            DB::table('goods_categories')->where('id' , $categoryId)->update(array(
                'goods_num'=>$count
            ));
        }
        Redis::del("helloo:business:goods:category:service:account:".$this->userId);
    }

}
