<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class BusinessSearchLog implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    private $user;
    private $content;
    private $shop;
    private $now;

    public function __construct($user , $content , $shop='')
    {
        $this->user = $user;
        $this->content = $content;
        $this->shop = $shop;
        $this->now = date('Y-m-d H:i:s');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        DB::table('business_search_logs')->insert(array(
            'user_id'=>$this->user,
            'owner'=>$this->shop,
            'content'=>$this->content,
            'created_at'=>$this->now
        ));
    }

}
