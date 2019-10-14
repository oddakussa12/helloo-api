<?php


namespace App\Resources;

use Illuminate\Http\Resources\Json\Resource;

class PostTranslationCollection extends Resource
{
    /**
     *
     */
    public function toArray($request)
    {
        return [
            'post_locale' => $this->post_locale,
            'post_title' => $this->post_title,
            'post_content' => $this->post_content,
//            'translations' => PostTranslationCollection::collection($this->translations),
//            'CategoryTranslation' => $this->translations,
            ];
    }
}