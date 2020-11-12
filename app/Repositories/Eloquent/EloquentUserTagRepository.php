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

    public function getByUserId($userId)
    {
        return $this->model->where('user_id' , $userId)->get();
    }


}
