<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class BlockUser extends Model
{

    protected $table = "block_users";

    protected $primaryKey = 'id';

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    public $dateFormat = "U";


    protected $fillable = [
        'user_id',
        'blocked_user_id',
        'is_deleted',
        'created_at',
        'updated_at'
    ];

}
