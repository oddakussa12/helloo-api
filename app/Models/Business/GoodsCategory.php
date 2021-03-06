<?php

namespace App\Models\Business;

use Illuminate\Database\Eloquent\Model;


class GoodsCategory extends Model
{

    protected $table = "goods_categories";

    protected $primaryKey = "category_id";

    public $incrementing = false;

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    protected $fillable = ['user_id' , 'name' , 'sort' ,  'created_at' , 'updated_at' , 'default'];

    protected $hidden = ['created_at' , 'updated_at'];

    protected $casts = [
      "category_id"=>'string',
      "user_id"=>'string'
    ];

    protected $appends = ['is_default'];

    public function getIsDefaultAttribute()
    {
        return boolval($this->default);
    }

}
