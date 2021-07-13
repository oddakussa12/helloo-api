<?php

namespace App\Http\Controllers\V1;

use Carbon\Carbon;
use Dingo\Api\Exception\StoreResourceFailedException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnswerController extends BaseController
{
    /**
     * @deprecated
     * @note 问题新增
     * @datetime 2021-07-12 18:02
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        $answer = '';
        if($user->user_activation==1&&$user->user_answer==0)
        {
            $userId = $user->user_id;
            $answers = (array)$request->all();
            $answers = array_filter($answers , function($v , $k){
                return !blank($k)&&in_array($v , array(0, 1))&&in_array($k , array("one" , 'two' , 'three' , 'four' , 'five' , 'six'));
            } , ARRAY_FILTER_USE_BOTH);
            $count = count($answers);
            $dateTime = Carbon::now()->toDateTimeString();
            if($count==6)
            {
                $answers = collect($answers)->map(function ($v, $key) use ($userId ,$dateTime , &$answer){
                    switch ($key)
                    {
                        case 'one':
                            $qId = 1;
                            if($v==0)
                            {
                                $answer = $answer.strval(1);
                            }else{
                                $answer = $answer.strval(2);
                            }
                            break;
                        case 'two':
                            $qId = 2;
                            break;
                        case 'three':
                            $qId = 3;
                            break;
                        case 'four':
                            $qId = 4;
                            break;
                        case 'five':
                            $qId = 5;
                            break;
                        default:
                            if($v==0)
                            {
                                $answer = $answer.strval(1);
                            }else{
                                $answer = $answer.strval(2);
                            }
                            $qId = 6;
                            break;
                    }
                    return array('user_id'=>$userId , 'question_id'=>$qId , 'answer'=>$v , 'created_at'=>$dateTime);
                })->toArray();
                DB::beginTransaction();
                try{
                    DB::table('answers')->insert($answers);
                    DB::table('users')->where('user_id' , $userId)->update(
                        array(
                            'user_answer'=>1,
                            'user_answered_at'=>$dateTime
                        )
                    );
                    DB::commit();
                }catch (\Exception $e)
                {
                    DB::rollBack();
                    \Log::error('submit_answer_failed:'.\json_encode($e->getMessage() , JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
                    throw new StoreResourceFailedException('answer submission failed!');
                }
            }
        }
        if($answer == '12')
        {
            $type = "A";
        }elseif ($answer == '22')
        {
            $type = "B";
        }elseif ($answer == '11')
        {
            $type = "C";
        }elseif ($answer == '21')
        {
            $type = "D";
        }else{
            $type = 'E';
        }
        return $this->response->created(null , array(
            'type'=>$type
        ));
    }

}
