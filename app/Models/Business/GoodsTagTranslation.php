<?php

namespace App\Models\Business;

use Illuminate\Database\Eloquent\Model;


class GoodsTagTranslation extends Model
{
    protected $table = "goods_tags_translations";

    protected $primaryKey = "id";

    public $incrementing = false;

    protected $fillable = ['locale' , 'tag_content'];

    protected $hidden = ['id'];

}
