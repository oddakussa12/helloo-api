<?php

/**
 * @Author: Dell
 * @Date:   2019-11-13 11:42:21
 * @Last Modified by:   Dell
 * @Last Modified time: 2019-11-19 01:37:05
 */
namespace App\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\Resource;

class RyChatCollection extends Resource
{
    public function toArray($request)
    {
        return [
            'chat_id' => $this->chat_id,
            'chat_from_id' => $this->chat_from_id,
            'chat_to_id' => $this->chat_to_id,
            'chat_content' => $this->chat_content,
            'chat_time' => $this->chat_time,
            'chat_msg_type' => $this->chat_msg_type,
            'chat_format_time' => Carbon::createFromTimestampMs($this->chat_time)->diffForHumans(),
        ];
    }
}