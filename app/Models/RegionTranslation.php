<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegionTranslation extends Model
{

    protected $primaryKey = 'region_translation_id';

    protected $fillable = ['region_translation_id' , 'region_locale','region_name'];

    protected $table = 'regions_translations';

    public $timestamps = false;

}
