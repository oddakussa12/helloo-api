<?php

namespace App\Models\Business;

use Illuminate\Database\Eloquent\Model;


class DelaySpecialGoods extends Model
{
    protected $table = "special_goods";

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    protected $fillable = ['delay_id' , 'shop_id' , 'goods_id' , 'special_price' , 'free_delivery' , 'packaging_cost' , 'start_time' , 'deadline' , 'admin_id'];

    protected $hidden = ['admin_id'];
}
