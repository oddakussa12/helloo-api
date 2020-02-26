<?php

namespace App\Models;

use App\Events\UserProfileEvent;
use Illuminate\Database\Eloquent\Model;


class UserProfileLike extends Model
{

    protected $table = "user_profile_likes";
    
    protected $fillable = ['profile_user_id' , 'user_id'];

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    protected $casts = ['status' => 'boolean'];

    public $paginateParamName = 'profile_like_page';

//    protected $dispatchesEvents = [
//        'created' => UserProfileEvent::class,
//    ];


}
