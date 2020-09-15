<?php

namespace App\Exceptions\Exception;

use RuntimeException;
use App\Models\User;
use App\Models\Post;
use Illuminate\Support\Arr;
use App\Models\PostComment;

class ModelNotFoundException extends RuntimeException
{
    /**
     * Set the affected Eloquent model and instance ids.
     *
     * @param  string  $model
     * @param  int|array  $ids
     * @return $this
     */
    public function setModel($model, $ids = [])
    {
        $this->model = $model;
        $this->ids = Arr::wrap($ids);

        if($model==User::class)
        {
            $message = 'Sorry, this account does not exist or is blocked!';
        }elseif ($model==Post::class)
        {
            $message = 'Sorry, this post does not exist or was deleted!';
        }elseif ($model==PostComment::class)
        {
            $message = 'Sorry, this comment does not exist or has been deleted!';
        }else{

            $message = 'Sorry, this resource does not exist or has been deleted!';
        }
        $this->message = $message;

//        if (count($this->ids) > 0) {
//            $this->message .= ' '.implode(', ', $this->ids);
//        } else {
//            $this->message .= '.';
//        }

        return $this;
    }

}
