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
    /**
     * @note 好友
     * @datetime 2021-07-12 19:26
     * @param $toId
     * @param null $perPage
     * @param string[] $columns
     * @param string $pageName
     * @param null $page
     * @return mixed
     */
    public function paginateByUser($toId , $perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
    {
        $pageName = isset($this->model->paginateParamName)?$this->model->paginateParamName:$pageName;
        return $this->model->where('request_to_id' , $toId)->orderBy($this->model->getCreatedAtColumn(), 'DESC')->paginate($perPage , $columns , $pageName , $page);
    }
}
