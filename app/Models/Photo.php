<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Photo extends Model
{

    protected $table = "users_photos";

    protected $primaryKey = 'photo_id';

    protected  $guarded=['*'];

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'photo_id',
        'user_id',
        'photo',
        'like',
        'created_at',
    ];

}
