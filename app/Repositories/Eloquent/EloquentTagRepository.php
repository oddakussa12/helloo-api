<?php
/**
 * Created by PhpStorm.
 * User: Dell
 * Date: 2019/5/19
 * Time: 18:35
 */
namespace App\Repositories\Eloquent;

use App\Repositories\EloquentBaseRepository;
use App\Repositories\Contracts\TagRepository;

class EloquentTagRepository  extends EloquentBaseRepository implements TagRepository
{

    public function all()
    {
        $tags = $this->model;
        if (method_exists($this->model, 'translations')) {
            return $tags->with('translations')
                ->orderBy('tag_sort', 'DESC')
                ->orderBy($this->model->getCreatedAtColumn(), 'DESC')
                ->get();
        }
        return $tags->orderBy('tag_sort', 'DESC')->orderBy($this->model->getCreatedAtColumn(), 'DESC')->get();
    }

    public function hot()
    {
        $tags = $this->model;
        if (method_exists($this->model, 'translations')) {
            return $tags->with('translations')->orderBy('tag_sort', 'DESC')->limit(7)->get();
        }
        return $tags->orderBy('tag_sort', 'DESC')->limit(7)->get();
    }

}
