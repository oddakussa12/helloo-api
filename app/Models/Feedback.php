<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Feedback extends Model
{

    protected $table = "feedback";

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    protected $primaryKey = 'id';

    protected $fillable = ['user_id' , 'content','image'];


    public function setUpdatedAt($value)
    {

    }

}
