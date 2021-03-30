<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class GreatUserScoreUpdate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    private $userId;
    private $type;
    /**
     * @var int
     */
    private $time;
    /**
     * @var array|mixed
     */
    private $relation;

    public function __construct($userId , $type , $relation='')
    {
        $this->userId = $userId;
        $this->type = $type;
        $this->relation = $relation;
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
            case 'gameI':
            case 'talkativeIII':
                $score = 300;
                break;
            case 'gameII':
                $score = 800;
                break;
            case 'gameIII':
                $score = 1500;
                break;
            case 'socialI':
                $score = 30;
                break;
            case 'socialII':
                $score = 150;
                break;
            case 'socialIII':
                $score = 350;
                break;
            case 'talkativeI':
                $score = 50;
                break;
            case 'talkativeII':
            case 'otherSchoolFriend':
                $score = 100;
                break;
            case 'maskCollection':
                $score = 250;
                break;
            default:
                $score = 0;
                break;
        }
        if($score!=0)
        {
            $userId = $this->userId;
            $data = array(
                'user_id'=>$userId,
                'type'=>$this->type,
                'relation'=>$this->relation,
                'created_at'=>$this->time,
            );
            try{
                DB::beginTransaction();
                $logResult = DB::table('users_scores_logs_'.$this->hashDbIndex($userId))->insert($data);
                if(!$logResult)
                {
                    throw new \Exception('user score log insert fail');
                }
                $score = DB::table('users_scores')->where('user_id' , $userId)->first();
                if(blank($score))
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
                if(intdiv($scoreResult)<=0)
                {
                    throw new \Exception('user score insert or update fail');
                }
                DB::commit();
            }catch (\Exception $e){
                DB::rollBack();
                Log::info('moreTimeUserScoreUpdateFile' , array(
                    'user_id'=>$userId,
                    'type'=>$this->type,
                    'relation'=>$this->relation,
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
