<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Sticker extends Model
{

    protected $table = "stickers";


    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';


    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'id',
        'status',
        'created_at',
        'updated_at',
    ];

}
