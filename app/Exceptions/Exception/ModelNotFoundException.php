<?php

namespace App\Exceptions\Exception;

use RuntimeException;
use App\Models\User;
use Illuminate\Support\Arr;

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
        }else{

            $message = 'Sorry, this resource does not exist or has been deleted!';
        }
        $this->message = $message;

        return $this;
    }

}
