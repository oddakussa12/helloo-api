<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class App extends Model
{

    protected $table = "app_versions";
    
    protected $fillable = ['platform' , 'version' , 'last' , 'upgrade_point' , 'status'];

    const CREATED_AT = 'created_at';

    protected $casts = ['status' => 'boolean'];

    public $paginateParamName = 'country_page';

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
        'status',
        'created_at',
    ];

}
