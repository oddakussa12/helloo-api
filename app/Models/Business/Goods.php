<?php

namespace App\Models\Business;

use Illuminate\Database\Eloquent\Model;


class Goods extends Model
{
    protected $table = "goods";

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    protected $casts = [
        'id' => 'string',
        'shop_id' => 'string',
        'user_id' => 'string',
        'image'=>'array'
    ];

    protected $appends = ['format_price'];

    protected $fillable = ['user_id' , 'shop_id' , 'name' , 'image' , 'like' , 'price', 'recommend', 'currency' ,'recommended_at', 'description', 'status' , 'liked_at'];

    protected $hidden = ['updated_at' , 'recommend' , 'recommended_at' , 'liked_at' , 'goods'];

    public function getFormatPriceAttribute()
    {
        return $this->price.' '. $this->currency;
    }

}
