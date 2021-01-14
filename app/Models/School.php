<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class School extends Model
{

    protected $table = "schools";
    
    protected $fillable = ['key' , 'name'];

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
        'country',
        'created_at',
    ];

}
