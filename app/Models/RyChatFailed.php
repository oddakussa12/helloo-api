<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class RyChatFailed extends Model
{
    use SoftDeletes;

    protected $table = "ry_chats_failed";

    const CREATED_AT = 'chat_created_at';


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
