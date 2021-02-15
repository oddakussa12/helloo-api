<?php

namespace App\Jobs;

use App\Models\User;
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

    private $country;
    private $user_phone;
    private $user_phone_country;
    private $user;

    /**
     * UserSynchronization constructor.
     * @param $user
     * @param $extend
     * @param $geo
     */
    public function __construct($user , $extend , $geo)
    {
        $this->user = $user;
        $this->user_phone = $extend['user_phone'];
        $this->user_phone_country = $extend['user_phone_country'];
        $this->country = strtolower(strval($geo->iso_code));
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $country = '';
        $type = 0;
        if(blank($country)&&($this->user_phone_country==1||$this->user_phone_country==62))
        {
            if(substr($this->user_phone , 0 , 3)==473)
            {
                $type = 1;
                $country = 'gd';
            }
        }

        if(blank($country)&&(substr($this->user_phone , 0 , 4)==1473||$this->user_phone_country==473))
        {
            $type = 1;
            $country = 'gd';
        }

        if(blank($country)&&(substr($this->user_phone , 0 , 1)==7&&strlen($this->user_phone)==8))
        {
            $type = 1;
            $country = 'tl';
        }
        if(blank($country)&&$this->user_phone_country==230)
        {
            $type = 1;
            $country = 'mu';
        }
        if(blank($country))
        {
            $type = 0;
            $country = strtolower($this->country);
        }
        $createdAt = optional($this->user->makeVisible('user_created_at')->user_created_at)->toDateTimeString();
        DB::table('users_countries')->insert(array(
            'user_id'=>$this->user->user_id,
            'type'=>$type,
            'country'=>$country,
            'created_at'=>$createdAt,
        ));
    }

}
