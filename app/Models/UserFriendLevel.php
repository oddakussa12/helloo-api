<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


/**
 * Class FriendSignIn
 * @package App\Models
 * 用户好友关系签到表
 */
class UserFriendLevel extends Model
{

    protected $table = "users_friends_level";

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    public $dateFormat = "U";

    protected $primaryKey = 'id';

    protected $fillable = ['relationship_id', 'heart_count', 'score', 'level_id', 'user_id', 'friend_id', 'status', 'is_delete', 'updated_at'];

}
