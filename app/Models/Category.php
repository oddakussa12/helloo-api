<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Dimsav\Translatable\Translatable;

class Category extends Model
{
	use Translatable;

    protected $table = "categories";

    const CREATED_AT = 'category_created_at';

    const UPDATED_AT = 'category_updated_at';

    protected $primaryKey = 'category_id';

    public $translatedAttributes = ['category_locale' , 'category_name'];

    protected $fillable = ['category_id' , 'category_sort' , 'category_status','category_isdel'];

    public $translationModel = 'App\Models\CategoryTranslation';

    public $paginateParamName = 'category_page';

    protected $localeKey = 'category_locale';
}
