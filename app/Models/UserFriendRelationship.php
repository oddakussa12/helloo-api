<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


/**
 * Class FriendSignIn
 * @package App\Models
 * 用户好友关系签到表
 */
class UserFriendRelationship extends Model
{

    protected $table = "users_friends_relationship";

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    public $dateFormat = "U";

    protected $primaryKey = 'id';

    protected $fillable = ['name', 'alias_name', 'level', 'is_delete'];

}