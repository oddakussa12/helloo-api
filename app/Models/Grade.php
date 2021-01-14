<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Grade extends Model
{

    protected $table = "grades";
    
    protected $fillable = ['key' , 'name' , 'type'];

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
        'type',
        'created_at',
    ];

}
