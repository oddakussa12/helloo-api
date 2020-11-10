<?php
/**
 * Created by PhpStorm.
 * User: Dell
 * Date: 2019/5/19
 * Time: 18:35
 */
namespace App\Repositories\Eloquent;

use App\Repositories\EloquentBaseRepository;
use App\Repositories\Contracts\UserTagRepository;

class EloquentUserTagRepository  extends EloquentBaseRepository implements UserTagRepository
{

    public function getByUserIds($userIds)
    {
        return $this->model->whereIn('user_id' , $userIds)->get();
    }

}
