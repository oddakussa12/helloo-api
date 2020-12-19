<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class SystemNotification extends Model
{

    protected $table = "system_notifications";
    
    protected $fillable = ['notification_id' , 'from_id' , 'type' , 'content' , 'cover_url' , 'video_url' , 'jump_url' , 'is_expired' , 'expired_at'];

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    public $timestamps = true;

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'id',
        'from_id',
        'is_expired',
        'updated_at',
        'expired_at',
    ];

}
