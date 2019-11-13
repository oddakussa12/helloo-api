<?php

/**
 * @Author: Dell
 * @Date:   2019-10-24 14:57:52
 * @Last Modified by:   Dell
 * @Last Modified time: 2019-11-13 14:15:08
 */
namespace App\Repositories\Eloquent;

use App\Repositories\EloquentBaseRepository;
use App\Repositories\Contracts\PyChatRepository;


class EloquentPyChatRepository  extends EloquentBaseRepository implements PyChatRepository
{

    public function showMassageByUserId($user_id)
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
    public function showMassageByRoomUuid($room_uuid)
    {
        return $this->model
                    ->where(['chat_type'=>'room'])
                    ->where(['to_id'=>$room_uuid])
                    ->orderBy($this->model->getCreatedAtColumn(), 'DESC')
                    ->paginate($this->perPage , ['*'] , $this->pageName);
    }
}