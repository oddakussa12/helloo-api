<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Video extends Model
{

    protected $table = "users_videos";

    protected $primaryKey = 'video_id';

    protected  $guarded=['*'];

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'user_id',
        'image',
        'video_url',
        'created_at',
    ];

}
