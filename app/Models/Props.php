<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Props extends Model
{

    protected $table = "props";
    
    protected $fillable = ['cover' , 'url' , 'type'];

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $paginateParamName = 'props_page';

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'id',
//        'created_at',
        'category',
        'default',
        'recommendation',
        'updated_at',
        'deleted_at',
    ];

}
