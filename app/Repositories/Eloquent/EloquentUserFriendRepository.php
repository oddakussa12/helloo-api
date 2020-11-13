<?php
/**
 * Created by PhpStorm.
 * User: Dell
 * Date: 2019/5/19
 * Time: 18:35
 */
namespace App\Repositories\Eloquent;

use App\Repositories\EloquentBaseRepository;
use App\Repositories\Contracts\UserFriendRepository;

class EloquentUserFriendRepository  extends EloquentBaseRepository implements UserFriendRepository
{
    public function paginateByUser($userId)
    {
        return $this->model->where('user_id' , $userId)->orderBy($this->model->getCreatedAtColumn(), 'DESC')->paginate($this->perPage , ["*"] , $this->pageName);
    }
    public function getAllByUser($userId, $perPage = 15)
    {
//        return $this->model->where('user_id' , $userId)->orderBy($this->model->getCreatedAtColumn(), 'DESC')->paginate($this->perPage , ['*'] , $this->pageName);
    }
}
