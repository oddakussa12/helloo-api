<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoryTranslation extends Model
{
	public $timestamps = false;

    protected $primaryKey = 'category_translation_id';

    protected $fillable = ['category_translation_id','category_locale' , 'category_name'];

    protected $table = 'categories_translations';
}
