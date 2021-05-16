<?php

namespace App\Jobs;

use App\Models\User;
use App\Repositories\Contracts\UserRepository;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;

class ShopSyncUser implements ShouldQueue
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
        if(isset($data['nick_name']))
        {
            $field['user_nick_name'] = $data['nick_name'];
        }
        if(isset($data['avatar']))
        {
            $field['user_avatar'] = $data['avatar'];
        }
        if(isset($data['cover']))
        {
            $field['user_cover'] = $data['cover'];
        }
        if(!empty($field))
        {
            app(UserRepository::class)->update($this->user , $field);
        }

        if(!empty($this->name))
        {
            $key = 'helloo:account:service:account-username-change';
            $username = trim(strval($this->name));
            if(!blank($username))
            {
                $user = $this->user;
                $oldName = $user->user_name;
                if($oldName==$username)
                {
                    return;
                }
                $index = ($user->user_id)%2;
                $usernameKey = 'helloo:account:service:account-username-'.$index;
                $rules = array(
                    'user_name' => [
                        'bail',
                        'required',
                        'string',
                        'alpha_num',
                        'between:1,20',
                        function ($attribute, $value, $fail) use ($user , $key){
                            $score = Redis::zscore($key , $user->user_id);
                            if($score!==null)
                            {
                                $fail('You can only change your username once within a year!');
                            }
                        },
                        function ($attribute, $value, $fail) use ($usernameKey){
                            if(Redis::sismember($usernameKey , strtolower($value)))
                            {
                                $fail(__('Nickname taken already.'));
                            }
                            $exist = DB::table('users')->where('user_name' , $value)->first();
                            if(!blank($exist))
                            {
                                $fail(__('Nickname taken already.'));
                            }
                        }
                    ],
                );
                $validationField = array(
                    'user_name' => $username
                );
                Validator::make($validationField, $rules)->validate();
                $key = 'helloo:account:service:account-username-change';
                $now = Carbon::now();
                DB::beginTransaction();
                try {
                    $createdAt = $now->toDateTimeString();
                    $count = DB::table('users')->where('user_id' , $user->user_id)->increment('user_name_change' , 1 ,
                        array(
                            'user_name'=>$username,
                            'user_name_changed_at'=>$createdAt,
                        )
                    );
                    $result = DB::table('users_names_logs')->insert(array(
                        'user_id'=>$user->user_id,
                        'user_name'=>$oldName,
                        'created_at'=>$createdAt,
                    ));
                    if($count>0&&$result)
                    {
                        Redis::sadd($usernameKey , strtolower($username));
                        Redis::zadd($key , $now->timestamp , $user->user_id);
                        DB::commit();
                    }else{
                        throw new \Exception('Database update failed');
                    }
                    substr($user->user_name , 0 , 3)=='lb_' && OneTimeUserScoreUpdate::dispatch($user , 'fillName')->onQueue('helloo_{one_time_user_score_update}');
                }catch (\Exception $e)
                {
                    DB::rollBack();
                    Log::info('username_update_fail' , array(
                        'message'=>$e->getMessage(),
                        'user_id'=>$user->user_id,
                        'user_name'=>$user->user_name,
                        'username'=>$username
                    ));
                }

            }
        }
    }

}
