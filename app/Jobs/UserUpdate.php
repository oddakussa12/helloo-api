<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class UserUpdate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    private $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $data = array(
            'id'=>$this->user->getKey(),
            'name'=>$this->user->user_nick_name,
            'portrait'=>userCover($this->user->user_avatar),
        );
        Log::info('user_update_data' , $data);
        $result = app('rcloud')->getUser()->update($data);
        Log::info('user_update_result' , $result);
    }

}
