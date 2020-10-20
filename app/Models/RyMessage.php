<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class RyMessage extends Model
{


    protected $table = "ry_messages";

    const CREATED_AT = 'created_at';

    protected $primaryKey = 'id';


    protected $fillable = [
        'message_id',
        'message_content',
        'message_type',
        'message_time',
    ];

    public function setUpdatedAt($value)
    {

    }


}
