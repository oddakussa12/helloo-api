<?php
/**
 * Created by PhpStorm.
 * User: Dell
 * Date: 2019/5/19
 * Time: 18:35
 */
namespace App\Repositories\Eloquent;

use App\Repositories\EloquentBaseRepository;
use App\Repositories\Contracts\UserFriendRequestRepository;

class EloquentUserFriendRequestRepository  extends EloquentBaseRepository implements UserFriendRequestRepository
{
    public function paginateByUser($toId , $perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
    {
        $pageName = isset($this->model->paginateParamName)?$this->model->paginateParamName:$pageName;
        return $this->model->where('request_to_id' , $toId)->orderBy($this->model->getCreatedAtColumn(), 'DESC')->paginate($perPage , $columns , $pageName , $page);
    }

    public function batchCreate($data)
    {
        return $this->model->insert($data);
    }
}
