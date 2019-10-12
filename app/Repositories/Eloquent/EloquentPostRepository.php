<?php

/**
 * @Author: Dell
 * @Date:   2019-08-09 21:23:30
 * @Last Modified by:   Dell
 * @Last Modified time: 2019-09-18 14:38:11
 */
namespace App\Repositories\Eloquent;

use Illuminate\Http\Request;
use App\Repositories\EloquentBaseRepository;
use App\Repositories\Contracts\UserRepository;
use App\Repositories\Contracts\PostRepository;


class EloquentPostRepository  extends EloquentBaseRepository implements PostRepository
{

    public function all()
    {
        if (method_exists($this->model, 'translations')) {
            return $this->model->with('translations')->orderBy($this->model->getCreatedAtColumn(), 'DESC')->get();
        }
        return $this->model->orderBy($this->model->getCreatedAtColumn(), 'DESC')->get();
    }

    public function top($request)
    {
        $type = $request->get('type' , 'world');
        if($type=='world')
        {
            $posts = $this->model->where('post_category_id', '!=' , 0);
        }else{
            $posts = $this->model->where('post_category_id' , 1);
        }
        if (method_exists($this->model, 'translations')) {
            return $posts->with('translations')->where('post_topping' , 1)->orderBy('post_topped_at', 'DESC')->orderBy('post_like_num', 'DESC')->limit(10)->get();
        }
        return $posts->where('post_topping' , 1)->orderBy('post_topped_at', 'DESC')->orderBy('post_like_num', 'DESC')->orderBy($this->model->getCreatedAtColumn(), 'DESC')->limit(10)->get();
    }

    public function paginate($perPage = 10, $columns = ['*'], $pageName = 'page', $page = null)
    {
        $pageName = isset($this->model->paginateParamName)?$this->model->paginateParamName:$pageName;
        if (method_exists($this->model, 'translations')) {
            return $this->model->with('translations')->where('post_topping' , 0)->orderByDesc('post_like_num')->paginate($perPage , $columns , $pageName , $page);
        }
        return $this->model->orderByDesc('post_like_num')->paginate($perPage , $columns , $pageName , $page);
    }
	public function hot($request)
    {
        $posts = $this->model;
        if (method_exists($this->model, 'translations')) {
            return $posts->with('translations')->orderBy('post_rate', 'DESC')->orderBy('post_like_num', 'DESC')->orderBy($this->model->getCreatedAtColumn(), 'DESC')->limit(6)->get();
        }
        return $posts->orderBy('post_like_num', 'DESC')->orderBy($this->model->getCreatedAtColumn(), 'DESC')->limit(6)->get();
    }

    public function paginateAll(Request $request)
    {
        $appends = array();
        $posts = $this->allWithBuilder();
        if ($request->get('home')!== null){
//            $posts = $posts->where('post_topping' , 0);
            $appends['home'] = $request->get('home');
//            $posts = $posts->inRandomOrder()->paginate($this->perPage , ['*'] , $this->pageName);
            // $categoryId = $request->get('categoryId' , 1);
            // if($categoryId == 1)
            // {
            //     $appends['categoryId'] = $categoryId;
            //     $posts->where('post_category_id' , $categoryId);
            // }else if($categoryId=='group'){
            //     $appends['categoryId'] = $categoryId;
            //     $posts = $posts->where('post_category_id', '!=' , 0);
            // }else{
            //     $posts = $posts->where('post_category_id' , 0);
            // }
            //->orderBy('post_topping', 'desc')->orderBy('post_topped_at', 'desc')
            if($request->get('tag')!==null)
            {
                $tag = $request->get('tag');
                $appends['tag'] = $tag;
                $posts = $posts->withAnyTags([$tag]);
            }
            $posts = $posts
                ->orderBy($this->model->getCreatedAtColumn(), 'DESC')
                ->orderBy('post_rate', 'DESC')
                ->orderBy('post_like_num', 'DESC')
                ->paginate($this->perPage , ['*'] , $this->pageName);
            return $posts->appends($appends);
    }
	if($request->get('tag')!==null)
    {
        $tag = $request->get('tag');
        $appends['tag'] = $tag;
        $posts = $this->model->withAnyTags([$tag])->orderBy('post_rate', 'desc')->orderBy($this->model->getCreatedAtColumn(), 'DESC')->paginate($this->perPage , ['*'] , $this->pageName);
        return $posts->appends($appends);
    }
    if ($request->get('take')!== null){
        $take = intval($request->get('take'));
        $take = $take>15||$take<1?10:$take;
        return $posts->inRandomOrder()->take($take)->get();
    }
    if ($request->get('keywords') !== null) {
        $keywords = $request->get('keywords');
        $appends['keywords'] = $keywords;
        $posts->whereHas('translations', function ($query) use ($keywords) {
            $query->where('post_title', 'LIKE', "%{$keywords}%");
        });
    }
    if ($request->get('categoryId') !== null) {
        $categoryId = $request->get('categoryId');
        if(in_array($categoryId , array(1 , 2 , 3)))
        {
            $appends['categoryId'] = $categoryId;
            $posts->where('post_category_id' , $categoryId);
        }else if($categoryId=='group'){
            $appends['categoryId'] = $categoryId;
            $posts->whereNotIn('post_category_id' , ['1']);
        }
    }
    if ($request->get('order_by') !== null && $request->get('order') !== null) {
        $order = $request->get('order') === 'asc' ? 'asc' : 'desc';
        $orderBy = $request->get('order_by' , 'post_like_num');
        $appends['order'] = $order;
        $appends['order_by'] = $orderBy;
        $posts->orderBy($orderBy, $order);
    }
    $posts = $posts->paginate($this->perPage , ['*'] , $this->pageName);
    return $posts->appends($appends);
    }

    public function findByUuid($uuid)
    {
        if (method_exists($this->model, 'translations')) {
            return $this->model->with('translations')->where('post_uuid', $uuid)->firstOrFail();
        }
        return $this->model->where('post_uuid', $uuid)->firstOrFail();
    }

    public function paginateByUser(Request $request , $userId)
    {
        $appends = array();
        $user = app(UserRepository::class)->findOrFail($userId);
        $posts = $user->posts();
        if ($request->get('order_by') !== null && $request->get('order') !== null) {
            $order = $request->get('order') === 'asc' ? 'asc' : 'desc';
            $orderBy = $request->get('order_by' , 'post_like_num');
            $appends['order'] = $order;
            $appends['order_by'] = $orderBy;
            $posts->orderBy($orderBy, $order);
        }else{
            $posts->orderBy('post_created_at' , 'desc');
        }
        if ($request->get('categoryId') !== null) {
            $categoryId = $request->get('categoryId');
            $appends['categoryId'] = $categoryId;
            $posts->where('post_category_id' , $categoryId);
        }
        $posts = $posts->paginate($this->perPage , ['*'] , $this->pageName);
        return $posts->appends($appends);
    }

    public function getCountByUserId($request , $user_id)
    {
        return $this->model
            ->where(['user_id'=>$user_id])
            ->count();
    }

}
