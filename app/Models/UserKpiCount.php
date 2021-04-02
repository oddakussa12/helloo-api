<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class UserKpiCount extends Model
{

    protected $table = "users_kpi_counts";

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
        'sent',
        'props',
        'like_video',
        'liked_video',
        'game_score',
        'friend',
        'other_school_friend',
    ];

}
