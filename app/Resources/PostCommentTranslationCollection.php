<?php


namespace App\Resources;

use App\Resources\PostCollection;
use Illuminate\Http\Resources\Json\Resource;

class PostCommentTranslationCollection extends Resource
{
    /**
     *
     */
    public function toArray($request)
    {
        return [
            'comment_locale' => $this->comment_locale,
            'comment_content' => $this->comment_content,
            ];
    }
}