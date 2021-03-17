<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Question extends Model
{

    protected $table = "questions";

    protected  $guarded=['*'];

    const CREATED_AT = 'created_at';

//    protected $casts = ['status' => 'boolean'];

    public $paginateParamName = 'page';

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
        'status',
        'created_at',
    ];

}
