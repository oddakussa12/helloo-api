<?php

/**
 * @Author: Dell
 * @Date:   2019-11-13 11:42:21
 * @Last Modified by:   Dell
 * @Last Modified time: 2019-11-13 14:54:42
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
            'chat_default_locale' => $this->chat_default_locale,
            'chat_default_massage' => $this->chat_default_massage,
            'chat_locale' => $this->chat_locale,
            'chat_massage' => $this->chat_massage,
            'chat_massage_format_created_at' => $this->chat_massage_format_created_at,
        ];
    }
}