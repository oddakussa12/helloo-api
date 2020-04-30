<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\PostView;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class GeneratePostView extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:post_view';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate post view';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        $yesterday = Carbon::yesterday();
        $viewData = array();
        $key = 'post_view_'.$yesterday->toDateString();
        while(true) {
            $data = Redis::Lpop($key);
            if(empty($data))
            {
                if(!empty($viewData))
                {
                    PostView::insert($viewData);
                }
                break;
            }else{
                $data = \json_decode($data , true);
                $addresses = geoip($data['ip']);
                $post_view_created_at = isset($data['timestamp'])?Carbon::createFromTimestamp($data['timestamp'])->toDateTimeString():$yesterday->toDateString().' 00:00:01';
                array_push($viewData , array(
                    'post_id' => $data['post_id'],
                    'user_id' => $data['user_id'],
                    'post_view_ip' => $data['ip'],
                    'view_country' => $addresses->country,
                    'view_state' => $addresses->state_name,
                    'view_city'=> $addresses->city,
                    'post_view_created_at'=>$post_view_created_at
                ));
                if(count($viewData)>=50)
                {
                    PostView::insert($viewData);
                    $viewData = array();
                }
            }
        }

    }
}
