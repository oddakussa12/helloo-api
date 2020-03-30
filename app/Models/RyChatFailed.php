<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class RyChatFailed extends Model
{
    protected $table = "ry_chats_failed";

    const CREATED_AT = 'created_at';


    protected $primaryKey = 'id';


    protected $fillable = [
        'raw',
        'errors',
    ];


    public $paginateParamName = 'chat_fail_page';

    public function setUpdatedAt($value)
    {

    }
}
