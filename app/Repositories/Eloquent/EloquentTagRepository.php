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

    public function getByTags(array $tags)
    {
        return $this->model->whereIn('tag' , $tags)->get();
    }

}
