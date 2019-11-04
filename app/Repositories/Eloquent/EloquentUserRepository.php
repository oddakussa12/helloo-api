<?php
/**
 * Created by PhpStorm.
 * User: Dell
 * Date: 2019/5/19
 * Time: 18:35
 */
namespace App\Repositories\Eloquent;

use App\Repositories\EloquentBaseRepository;
use App\Repositories\Contracts\UserRepository;

class EloquentUserRepository  extends EloquentBaseRepository implements UserRepository
{
    public function getDefaultPasswordField()
    {
        return $this->model->default_password_field;
    }
    public function getDefaultNameField()
    {
        return $this->model->default_name_field;
    }
    public function getDefaultEmailField()
    {
        return $this->model->default_email_field;
    }

    public function store($data)
    {
        return $this->model->create($data);
    }

    public function likePost($userId)
    {
        $user = $this->model->where('user_id', $userId)->first();
        return $user->likePost->pluck('pivot.post_like_state');
    }

    public function findOrFail($userId)
    {
        return $this->model->findOrFail($userId);
    }

    public function findOauth($oauth,$id)
    {
        return $this->model->where(array('user_'.$oauth=>$id))->first();
    }

    public function findByUuid($user_id)
    {
        return $this->model->where('user_id', $user_id)->firstOrFail();
    }

    public function findByWhere($where)
    {
        return $this->model->where($where)->first();
    }

    public function findMyFollow($object)
    {
        $followers = $object->followings()->orderByDesc('common_follows.created_at')->paginate(10,['*'],'follow_page');

        $userIds = $followers->pluck('user_id')->all(); //获取分页user id

        $followerIds = userFollow($userIds);//重新获取当前登录用户信息

        $followers->each(function ($item, $key) use ($followerIds) {
            $item->user_follow_state = in_array($item->user_id , $followerIds);
        });
        return $followers;
    }

    public function findFollowMe($object)
    {
        $followers = $object->followers()->orderByDesc('common_follows.created_at')->paginate(10,['*'],'follow_page');

        $userIds = $followers->pluck('user_id')->all(); //获取分页user id

        $followerIds = userFollow($userIds);//重新获取当前登录用户信息

        $followers->each(function ($item, $key) use ($followerIds) {
            $item->user_follow_state = in_array($item->user_id , $followerIds);
        });
        
        return $followers;
    }

    public function findUserRanking($where){
         return $this->model->whereIN('user_id',$where)->get();
    }

}
