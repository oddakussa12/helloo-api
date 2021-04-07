<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class LikePhoto extends Model
{

    protected $table = "likes_photos";

    protected $primaryKey = 'id';

    protected  $guarded=['*'];

    const CREATED_AT = 'created_at';

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'liked_id',
        'created_at',
    ];

}
