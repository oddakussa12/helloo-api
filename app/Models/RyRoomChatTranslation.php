<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RyRoomChatTranslation extends Model
{
	public $timestamps = false;

    protected $table = 'ry_rooms_chats_translations';

    protected $fillable = ['room_chat_id','room_chat_locale','room_chat_content'];

    protected $primaryKey = 'room_chat_translation_id';
}
