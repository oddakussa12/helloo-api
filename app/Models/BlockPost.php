<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class BlockPost extends Model
{

    protected $table = "block_posts";

    protected $primaryKey = 'id';

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';


    public $dateFormat = "U";


    protected $fillable = [
        'user_id',
        'post_uuid',
        'is_deleted',
        'created_at',
        'updated_at',
    ];
}
