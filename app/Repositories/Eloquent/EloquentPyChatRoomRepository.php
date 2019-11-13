<?php

/**
 * @Author: Dell
 * @Date:   2019-11-13 10:34:13
 * @Last Modified by:   Dell
 * @Last Modified time: 2019-11-13 10:34:40
 */
namespace App\Repositories\Eloquent;

use App\Repositories\EloquentBaseRepository;
use App\Repositories\Contracts\PyChatRoomRepository;


class EloquentPyChatRoomRepository  extends EloquentBaseRepository implements PyChatRoomRepository
{

    public function showMassage($user_id)
    {
            return $this->model
                        ->where(['from_id'=>auth()->id()])
                        ->where(['to_id'=>$user_id])
                        ->orWhere(function ($query ) use ($user_id){
                            $query->where(['from_id'=>$user_id])
                                  ->where(['to_id'=>auth()->id()]);
                            })
                        ->orderBy($this->model->getCreatedAtColumn(), 'DESC')
                        ->paginate($this->perPage , ['*'] , $this->pageName);
        }
}