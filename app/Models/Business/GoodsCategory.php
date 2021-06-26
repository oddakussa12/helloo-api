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

    protected $fillable = ['user_id' , 'name' , 'created_at' , 'updated_at' , 'default'];

    protected $hidden = ['created_at' , 'updated_at'];

}
