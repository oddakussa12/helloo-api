<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserFriend extends Model
{
    public $timestamps = false;

    protected $table = "users_friends";

    const CREATED_AT = 'created_at';

    protected $primaryKey = 'id';

    protected $fillable = ['user_id' , 'friend_id' , 'relation'];

    public $paginateParamName = 'friend_page';

    public static function boot()
    {
        parent::boot();

        static::creating(function($model) {
            $model->created_at = time();
            return true;
        });
    }

    public function setUpdatedAt($value)
    {

    }

    public function getFormatCreatedAtAttribute()
    {
        return date('Y-m-d H:i:s' , $this->created_at);
    }
}
