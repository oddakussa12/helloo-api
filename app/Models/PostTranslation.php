<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class PostTranslation extends Model
{
    use Searchable;

	const CREATED_AT = 'post_translation_created_at';

    const UPDATED_AT = 'post_translation_updated_at';

    protected $primaryKey = 'post_translation_id';

    protected $fillable = ['post_translation_id','post_locale' , 'post_title','post_content'];

    protected $table = 'posts_translations';

    public function setPostContentAttribute($value)
    {
        $this->attributes['post_content'] = htmlspecialchars_decode($value , ENT_QUOTES);
    }
    public function setPostTitleAttribute($value)
    {
        $this->attributes['post_title'] = htmlspecialchars_decode($value , ENT_QUOTES);
    }

}
