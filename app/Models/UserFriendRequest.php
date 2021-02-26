<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserFriendRequest extends Model
{
    public $timestamps = false;

    protected $table = "friends_requests";

    const CREATED_AT = 'request_created_at';

    const UPDATED_AT = 'request_updated_at';

    protected $primaryKey = 'request_id';

    protected $fillable = ['request_from_id' , 'request_to_id' , 'request_state'];

    protected $hidden = ['id'];

    public $paginateParamName = 'friend_page';

    public static function boot()
    {
        parent::boot();

        static::creating(function($model) {
            $time = time();
            $model->request_created_at = $time;
            $model->request_updated_at = $time;
            return true;
        });

        static::updating(function($model) {
            $model->request_updated_at = time();
            return true;
        });
    }
}
