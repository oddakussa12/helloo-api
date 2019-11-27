<?php

/**
 * @Author: Dell
 * @Date:   2019-10-24 14:57:52
 * @Last Modified by:   Dell
 * @Last Modified time: 2019-11-19 00:59:52
 */
namespace App\Repositories\Eloquent;

use App\Repositories\EloquentBaseRepository;
use App\Repositories\Contracts\PyChatRepository;


class EloquentPyChatRepository  extends EloquentBaseRepository implements PyChatRepository
{

    public function showMessageByUserId($user_id)
    {
            return $this->model
                        ->where(['from_id'=>auth()->id()])
                        ->where(['to_id'=>$user_id])
                        ->where(['chat_type'=>'user'])
                        ->orWhere(function ($query ) use ($user_id){
                            $query->where(['from_id'=>$user_id])
                                  ->where(['to_id'=>auth()->id()]);
                            })
                        ->orderBy($this->model->getCreatedAtColumn(), 'DESC')
                        ->paginate($this->perPage , ['*'] , $this->pageName);
        }
    public function showMessageByRoomUuid($room_uuid)
    {
        return $this->model
                    ->where(['chat_type'=>'room'])
                    ->where(['to_id'=>$room_uuid])
                    ->orderBy($this->model->getCreatedAtColumn(), 'DESC')
                    ->paginate($this->perPage , ['*'] , $this->pageName);
    }
    public function limitMessage($chat_id,$room_uuid)
    {
         $result = $this->model
                    ->where(['chat_type'=>'room'])
                    ->where(['to_id'=>$room_uuid]);
                    if(!empty($chat_id)){
                         $result = $result->where('chat_id','<',$chat_id);
                    }

                    $result = $result->with('user')->with('translations')->orderBy('chat_id', 'DESC')->limit(5)->get();
                    return $result;
    }
}