<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class RyChatRaw extends Model
{
    protected $table = "ry_chats_raw";

    const CREATED_AT = 'created_at';


    protected $primaryKey = 'id';


    protected $fillable = [
        'chat_id',
        'raw',
        'chat_time',
    ];


    public $paginateParamName = 'chat_raw_page';

    public function setUpdatedAt($value)
    {

    }
}
