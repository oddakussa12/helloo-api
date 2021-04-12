<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Group extends Model
{

    protected $table = "groups";
    
    protected $fillable = ['user_id' , 'administrator' , 'name' , 'avatar' , 'member'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'user_id',
        'is_deleted',
    ];

}
