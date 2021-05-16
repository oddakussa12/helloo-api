<?php

namespace App\Jobs;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Validator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Validation\ValidationException;


class UserSyncShop implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $user;

    private $name;

    private $data;

    public function __construct(User $user , $data=array() , $name='')
    {
        $this->user = $user;
        $this->data = $data;
        $this->name = $name;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $data = $this->data;
        $field = array();
        if(isset($data['user_nick_name']))
        {
            $field['nick_name'] = $data['user_nick_name'];
        }
        if(isset($data['user_avatar']))
        {
            $field['avatar'] = $data['user_avatar'];
        }
        if(isset($data['user_cover']))
        {
            $field['cover'] = $data['user_cover'];
        }
        if(!empty($field)&&!empty($this->user->user_shop))
        {
            DB::table('shops')->where('id' , $this->user->user_shop)->update($field);
        }

        if(!empty($this->name)&&!empty($this->user->user_shop))
        {
            $params = array(
                'name'=>$this->name
            );
            $user = $this->user;
            $rules = [
                'name' => [
                    'bail',
                    'filled',
                    'string',
                    'alpha_num' ,
                    function ($attribute, $value, $fail) use ($user){
                        $shop = Shop::where('name', $value)->where('user_id', '!=', $user->user_id)->first();
                        if(!empty($shop))
                        {
                            $fail('Store Name already exists!');
                        }
                        $u = User::where('name', $value)->first();
                        if(!empty($u))
                        {
                            $fail('Store Name already exists!');
                        }
                    }
                ]
            ];
            try {
                Validator::make($params, $rules)->validate();
            } catch (ValidationException $exception) {
                throw new ValidationException($exception->validator);
            }
            !empty($params)&&Shop::where('id' , $this->user->user_shop)->update($params);
        }

    }


}
