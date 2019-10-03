<?php


namespace App\Resources;

use Illuminate\Http\Resources\Json\Resource;

class TagCollection extends Resource
{
    public function toArray($request)
    {
        return [
            'tag_id'=>$this->tag_id,
            'tag_slug'=>$this->tag_slug,
            'tag_name'=>empty($this->tag_name)?optional($this->translate(config('translatable.translate_default_lang')))->post_content:$this->tag_name,
        ];
    }
}