<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{

    public $primaryKey = 'id';

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';


    protected $fillable = ['tag'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'id',
        'status' ,
        'created_at',
        'updated_at',
    ];

    public $table = 'tags';

}
