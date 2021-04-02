<?php

namespace App\Jobs;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class OneTimeUserScoreUpdate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    private $user;
    private $type;
    /**
     * @var int
     */
    private $time;

    public function __construct(User $user , $type)
    {
        $this->user = $user;
        $this->type = $type;
        $this->time = Carbon::now()->toDateTimeString();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        switch ($this->type)
        {
            case 'fillAvatar':
            case 'fillAbout':
                $score = 20;
                break;
            case 'fillCover':
            case 'fillSchool':
                $score = 15;
                break;
            case 'fillSchoolOther':
                $score = -15;
                break;
            case 'fillName':
                $score = 5;
                break;
            case 'fiveMaskVideo':
                $score = 25;
                break;
            default:
                $score = 0;
                break;
        }
        if($score!=0)
        {
            $userId = $this->user->getKey();
            $data = array(
                'user_id'=>$userId,
                'type'=>$this->type,
                'created_at'=>$this->time,
            );
            try{
                DB::beginTransaction();
                $logResult = DB::table('users_scores_logs_'.$this->hashDbIndex($userId))->insert($data);
                if(!$logResult)
                {
                    throw new \Exception('user score log insert fail');
                }
                $userScore = DB::table('users_scores')->where('user_id' , $userId)->first();
                if(blank($userScore))
                {
                    $scoreResult = DB::table('users_scores')->insert(array(
                        'user_id'=>$userId,
                        'score'=>$score,
                        'created_at'=>$this->time,
                        'updated_at'=>$this->time,
                    ));
                }else{
                    $scoreResult = DB::table('users_scores')->where('user_id' , $userId)->increment('score' , $score , array(
                        'updated_at'=>$this->time,
                    ));
                }
                if(intval($scoreResult)<=0)
                {
                    throw new \Exception('user score insert or update fail');
                }
                DB::commit();
            }catch (\Exception $e){
                DB::rollBack();
                Log::info('oneTimeUserScoreUpdateFile' , array(
                    'user_id'=>$userId,
                    'type'=>$this->type,
                    'message'=>$e->getMessage(),
                ));
            }
        }
    }

    private function hashDbIndex($string , $hashNumber=8)
    {
        $checksum  = crc32(md5(strtolower(strval($string))));
        if(8==PHP_INT_SIZE){
            if($checksum >2147483647){
                $checksum  = $checksum&(2147483647);
                $checksum = ~($checksum-1);
                $checksum = $checksum&2147483647;
            }
        }
        return (abs($checksum) % intval($hashNumber));
    }

}
