<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class BlackUser extends Model
{

    protected $table = "black_users";

    protected $primaryKey = 'id';

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    public $dateFormat = "U";


    protected $fillable = [
        'user_id',
        'desc',
        'operator',
        'is_delete',
        'start_time',
        'end_time',
        'unoperator',
        'created_at',
        'updated_at'
    ];
}
