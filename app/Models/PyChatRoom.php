<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Dimsav\Translatable\Translatable;
use Illuminate\Database\Eloquent\SoftDeletes;

class PyChatRoom extends Model
{
     protected $table = "pychats_rooms";

    const CREATED_AT = 'room_created_at';

    const UPDATED_AT = 'room_updated_at';

    const DELETED_AT = 'room_deleted_at';

    protected $primaryKey = 'room_id';

    protected $fillable = [
        'room_id' ,
        'room_uuid' ,
        'room_state',
    ];

    public $paginateParamName = 'pychatroom_page';

    public $perPage = 15;
}
