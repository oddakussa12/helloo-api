<?php

/**
 * @Author: Dell
 * @Date:   2019-11-13 11:42:21
 * @Last Modified by:   Dell
 * @Last Modified time: 2019-11-18 15:45:36
 */
namespace App\Resources;

use Illuminate\Http\Resources\Json\Resource;

class PyChatCollection extends Resource
{
    public function toArray($request)
    {
        return [
            'user_id' => $this->user->user_id,
            'user_name' => $this->user->user_name,
            'user_avatar' => $this->user->user_avatar,
            'user_country' => $this->user->user_country,
            'user_is_guest' => $this->user->user_is_guest,
            'chat_id' => $this->chat_id,
            'chat_uuid' => $this->chat_uuid,
            'chat_default_locale' => $this->chat_default_locale,
            'chat_default_message' => $this->chat_default_message,
            'chat_locale' => $this->hasTranslation(locale())?$this->chat_locale:'',
            'chat_message' => $this->hasTranslation(locale())?$this->chat_message:'',
            'chat_message_format_created_at' => $this->chat_message_format_created_at,
        ];
    }
}