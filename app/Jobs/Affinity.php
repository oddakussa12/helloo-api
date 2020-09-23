<?php

namespace App\Jobs;

use App\Custom\Constant\Constant;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;

/**
 * Class Affinity
 * @package App\Jobs
 * 亲密关系
 */
class Affinity implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $user;
    private $request;
    private $type;

    public function __construct($user, $request, $type)
    {
        $this->user    = $user;
        $this->request = $request;
        $this->type    = $type;

    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $user    = $this->user;
        $request = $this->request;

        $userNickName = !empty($user->user_nick_name) ? $user->user_nick_name : ($user->user_name ? $user->user_name : 'some one');
        Jpush::dispatch($this->type, $userNickName, $request->friend_id)->onQueue(Constant::QUEUE_PUSH_NAME);
    }
}
