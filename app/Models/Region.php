<?php

namespace App\Models;

use Dimsav\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    use Translatable;

    public $primaryKey = 'region_id';

    public $timestamps=false;

    public $table = 'regions';

    protected $localeKey = 'region_locale';

    public $translatedAttributes = ['region_name'];

    public $translationModel = 'App\Models\RegionTranslation';

}
