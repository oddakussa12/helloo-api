<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class RyChat extends Model
{
    use SoftDeletes;

    protected $table = "ry_chats";

    const CREATED_AT = 'chat_created_at';

    const UPDATED_AT = 'chat_updated_at';

    const DELETED_AT = 'chat_deleted_at';

    protected $primaryKey = 'chat_id';


    protected $fillable = [
        'chat_msg_uid',
        'chat_from_id',
        'chat_from_name',
        'chat_from_extra',
        'chat_to_id',
        'chat_content',
        'chat_image',
        'chat_msg_type',
        'chat_channel_type',
        'chat_time',
        'chat_sensitive_type',
        'chat_source',
        'chat_group_to_id',
        'chat_raw'
    ];


    public $paginateParamName = 'chat_page';


}
