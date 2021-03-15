<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class UserSynchronization implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $user;
    /**
     * @var mixed|string
     */
    private $type;

    /**
     * UserSynchronization constructor.
     * @param $user
     * @param $type
     */
    public function __construct($user , $type="sign")
    {
        $this->user = $user;
        $this->type = $type;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if($this->type=='sign')
        {
            $country = '';
            $type = 0;
            $userPhone = DB::table('users_phones')->where('user_id' , $this->user->getKey())->first();
            if(blank($country)&&($userPhone->user_phone_country==1||$userPhone->user_phone_country==62))
            {
                if(substr($userPhone->user_phone , 0 , 3)==473)
                {
                    $type = 1;
                    $country = 'gd';
                }
            }

            if(blank($country)&&(substr($userPhone->user_phone , 0 , 4)==1473||$userPhone->user_phone_country==473))
            {
                $type = 1;
                $country = 'gd';
            }

            if(blank($country)&&(substr($userPhone->user_phone , 0 , 1)==7&&strlen($userPhone->user_phone)==8))
            {
                $type = 1;
                $country = 'tl';
            }

            if(blank($country)&&$userPhone->user_phone_country==230)
            {
                $type = 1;
                $country = 'mu';
            }

            if(blank($country)&&$userPhone->user_phone_country==62)
            {
                $type = 1;
                $country = 'id';
            }

            if(blank($country)&&$userPhone->user_phone_country==251)
            {
                $type = 1;
                $country = 'et';
            }

            if(blank($country))
            {
                $type = 0;
                $country = $userPhone->user_phone_country;
            }

            $createdAt = optional($this->user->makeVisible('user_created_at')->user_created_at)->toDateTimeString();
            DB::table('users_countries')->insert(array(
                'user_id'=>$this->user->user_id,
                'type'=>$type,
                'country'=>$country,
                'created_at'=>$createdAt,
            ));
        }else{
            DB::table('users_countries')->where('user_id' , $this->user->getKey())->update(array(
                'activation'=>1
            ));
        }
    }

}
