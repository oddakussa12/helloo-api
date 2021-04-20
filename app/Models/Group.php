<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Group extends Model
{

    protected $table = "groups";
    protected $casts = [
        'id' => 'string',
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

    public function getNameAttribute($value)
    {
        $name = \json_decode($value , true);
        if(is_array($name))
        {
            $name = array_values($name);
            $name = implode(',' , $name);
        }
        return $name==null?strval($value):$name;
    }

    public function getAvatarAttribute($value)
    {
        $avatar = \json_decode($value , true);
        if(is_array($avatar))
        {
            $avatar = array_values($avatar);
            $avatar = implode(',' , $avatar);
        }
        return $avatar==null?strval($value):$avatar;
    }

}
