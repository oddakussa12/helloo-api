<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class RyMessageCount extends Model
{

    protected $table = "ry_messages_counts";

    protected $primaryKey = 'id';

    protected  $guarded=['*'];

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'user_id',
        'like',
        'liked',
        'txt',
        'audio',
        'image',
        'message',
        'props',
        'like_video',
        'liked_video',
        'game_score',
        'friend',
        'other_school_friend',
    ];

}
