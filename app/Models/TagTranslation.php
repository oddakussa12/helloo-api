<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TagTranslation extends Model
{

    protected $primaryKey = 'tag_translation_id';

    protected $fillable = ['tag_translation_id' , 'tag_locale','tag_name'];

    protected $table = 'tags_translations';

    public $timestamps = false;

}
