<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


/**
 * Class FriendSignIn
 * @package App\Models
 * 用户好友关系升级历史表
 */
class UserFriendLevelHistory extends Model
{

    protected $table = "users_friends_level_history";

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    public $dateFormat = "U";

    protected $primaryKey = 'id';

    protected $fillable=['id','user_id','user_id_count','friend_id','friend_id_count',
                         'is_delete','score','talk_day','created_at','updated_at'];

}
