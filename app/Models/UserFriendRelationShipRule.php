<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


/**
 * Class FriendSignIn
 * @package App\Models
 * 用户好友关系规则表
 */
class UserFriendRelationShipRule extends Model
{

    protected $table = "users_friends_relationship_rule";

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    public $dateFormat = "U";

    protected $primaryKey = 'id';

    protected $fillable = ['relationship_id', 'name','score', 'desc','is_delete'];

}
