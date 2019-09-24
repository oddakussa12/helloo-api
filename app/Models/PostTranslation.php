<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostTranslation extends Model
{

	const CREATED_AT = 'post_translation_created_at';

    const UPDATED_AT = 'post_translation_updated_at';
    protected $primaryKey = 'post_translation_id';

    protected $fillable = ['post_translation_id','post_locale' , 'post_title','post_content','post_locale_isdefault'];

    protected $table = 'posts_translations';
}
