<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Bgm extends Model
{

    protected $table = "bgms";
    
    protected $fillable = ['name' , 'url' , 'hash' , 'time' , 'recommendation' , 'sort'];

    const CREATED_AT = 'created_at';


    public function setUpdatedAtAttribute($value) {
        // Do nothing.
    }

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'id',
        'sort',
        'is_delete',
        'created_at',
    ];

}
