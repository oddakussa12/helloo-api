<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{

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
