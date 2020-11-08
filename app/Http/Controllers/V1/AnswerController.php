<?php

namespace App\Http\Controllers\V1;

use Carbon\Carbon;
use Dingo\Api\Exception\StoreResourceFailedException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnswerController extends BaseController
{


    public function store(Request $request)
    {
        $user = auth()->user();
        if($user->user_activation==0)
        {
            $userId = $user->user_id;
            $answers = (array)$request->input('answers');
            $answers = array_filter($answers , function($v , $k){
                return !blank($v)&&!blank($k)&&!in_array($v , array(1, 2))&&!in_array($k , array("one" , 'two' , 'three' , 'four' , 'five' , 'six'));
            } , ARRAY_FILTER_USE_BOTH);
            $count = count($answers);
            $dateTime = Carbon::now()->toDateTimeString();
            if($count==6)
            {
                $answers = collect($answers)->map(function ($v, $key) use ($userId ,$dateTime){
                    switch ($key)
                    {
                        case 'one':
                            $qId = 1;
                        case 'two':
                            $qId = 2;
                        case 'three':
                            $qId = 3;
                        case 'four':
                            $qId = 4;
                        case 'five':
                            $qId = 5;
                        default:
                            $qId = 6;

                    }
                    return array('user_id'=>$userId , 'question_id'=>$qId , 'answer'=>$v , 'created_at'=>$dateTime);
                });
                DB::beginTransaction();
                try{
                    DB::table('answers')->insert($answers);
                    DB::table('users')->where('user_id' , $userId)->update(
                        array(
                            'user_activation'=>1,
                            'user_activated_at'=>$dateTime
                        )
                    );
                    DB::commit();
                }catch (\Exception $e)
                {
                    DB::rollBack();
                    \Log::error('sign_up_failed:'.\json_encode($e->getMessage() , JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
                    throw new StoreResourceFailedException('answer submission failed!');
                }
            }
        }
        return $this->response->created();
    }

}
