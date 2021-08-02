<?php

namespace App\Models\Business;

use Illuminate\Database\Eloquent\Model;


class SpecialGoods extends Model
{
    protected $table = "special_goods";

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';


    protected $fillable = ['shop_id' , 'goods_id' , 'special_price' , 'free_delivery' , 'packaging_cost' , 'deadline' , 'admin_id'];

    protected $hidden = ['admin_id'];
}
