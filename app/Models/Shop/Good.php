<?php

namespace App\Models\Shop;

use Illuminate\Database\Eloquent\Model;


class Good extends Model
{
    protected $table = "goods";

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    protected $fillable = ['user_id' , 'shop_id' , 'name' , 'image' , 'like' , 'price', 'recommend', 'recommended_at', 'description', 'status'];

}
