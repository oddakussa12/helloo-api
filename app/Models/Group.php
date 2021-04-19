<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Group extends Model
{

    protected $table = "groups";
    protected $casts = [
        'id' => 'string',
        'name' => 'array',
        'avatar' => 'array'
    ];
    protected $fillable = ['user_id' , 'administrator' , 'name' , 'avatar' , 'member' , 'is_deleted' , 'deleted_at'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'user_id',
        'is_deleted',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

}
