<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostCommentTranslation extends Model
{
    public $timestamps = false;

    protected $primaryKey = 'comment_translation_id';

    protected $fillable = ['comment_translation_id','comment_locale' , 'comment_content','comment_default_locale'];

    protected $table = 'posts_comments_translations';


    public function setCommentContentAttribute($value)
    {
        $this->attributes['comment_content'] = htmlspecialchars($value , ENT_QUOTES);
    }
}
