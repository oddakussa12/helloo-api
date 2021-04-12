<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class GroupMember extends Model
{

    protected $table = "groups_members";
    
    protected $fillable = ['user_id' , 'group_id' , 'role'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'id'
    ];

}
