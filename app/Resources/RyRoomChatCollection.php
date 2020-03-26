<?php

/**
 * @Author: Dell
 * @Date:   2019-11-13 11:42:21
 * @Last Modified by:   Dell
 * @Last Modified time: 2019-11-19 01:37:05
 */
namespace App\Resources;

use Illuminate\Http\Resources\Json\Resource;

class RyRoomChatCollection extends Resource
{
    public function toArray($request)
    {
        return [
            'room_chat_id' => $this->room_chat_id,
            'room_chat_type' => $this->room_chat_type,
            'room_chat_image' => $this->room_chat_image,
            'room_chat_default_locale' => $this->room_chat_default_locale,
            'room_chat_content' => optional($this->translate(locale()))->room_chat_content,
            'room_chat_default_content' => optional($this->translate($this->room_chat_default_locale))->room_chat_content,
            'room_chat_created_at' => optional($this->room_chat_created_at)->toDateTimeString(),
            'from'=> $this->when($this->relationLoaded('user') , function (){
                return new UserCollection($this->user);
            })
        ];
    }
}