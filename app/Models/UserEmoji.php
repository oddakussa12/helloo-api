<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class UserEmoji extends Model
{

    protected $table = "users_emoji";


    protected $primaryKey = 'id';

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    protected $fillable = ['user_id', 'emoji'];
}
