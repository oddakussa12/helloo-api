<?php

namespace App\Models\Business;

use Illuminate\Database\Eloquent\Model;


class CategoryGoods extends Model
{
    protected $table = "categories_goods";

    protected $primaryKey = "id";

    public $incrementing = false;

    const CREATED_AT = 'created_at';

    protected $fillable = ['category_id' , 'goods_id' , 'user_id' , 'created_at'];

    protected $hidden = ['created_at'];

    protected $casts = [
        "category_id"=>'string',
        "user_id"=>'string'
    ];

}
