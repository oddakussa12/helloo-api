<?php

namespace App\Models;

use Carbon\Carbon;
use Dimsav\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RyRoomChat extends Model
{
    use Translatable,SoftDeletes;

    protected $table = "ry_rooms_chats";

    const CREATED_AT = 'room_chat_created_at';

    const UPDATED_AT = 'room_chat_updated_at';

    const DELETED_AT = 'room_chat_deleted_at';

    protected $primaryKey = 'room_chat_id';

    public $translatedAttributes = ['room_chat_locale' , 'room_chat_content' ];

    protected $fillable = [
        'room_chat_uuid' ,
        'room_from_id' ,
        'room_id' ,
        'room_uuid' ,
        'room_chat_type',
        'room_chat_image',
        'room_chat_default_locale',
        'chat_default_locale' ,
        'room_chat_ip' ,
    ];

    public $translationModel = RyRoomChatTranslation::class;

    public $paginateParamName = 'ry_room_chat_page';

    protected $localeKey = 'room_chat_locale';

    public $perPage = 5;

    public function user()
    {
        return $this->belongsTo(User::class , 'room_from_id' , 'user_id');
    }
}
