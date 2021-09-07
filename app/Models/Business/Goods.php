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
        'image'=>'array',
        'price'=>'float',
        'quality'=>'float',
        'service'=>'float',
        'discounted_price'=>'float',
        'packaging_cost'=>'float',
    ];

    protected $appends = ['format_price' , 'format_discounted_price' , 'average_point' , 'format_packaging_cost'];

    protected $fillable = ['user_id' , 'shop_id' , 'name' , 'image' , 'like' , 'price', 'recommend', 'currency' ,'recommended_at', 'description', 'status' , 'liked_at' , 'discounted_price' , 'packaging_cost'];

    protected $hidden = ['updated_at' , 'recommend' , 'recommended_at' , 'liked_at' , 'reply' , 'point' , 'quality' , 'service' , 'comment' , 'purchase_price'];

    public function getFormatPriceAttribute()
    {
        return sprintf("%1\$.2f", $this->price).' '. $this->currency;
    }

    public function getFormatDiscountedPriceAttribute()
    {
        return sprintf("%1\$.2f", $this->discounted_price).' '. $this->currency;
    }

    public function getAveragePointAttribute()
    {
        if(empty($this->comment))
        {
            return 0;
        }
        return round($this->point/$this->comment , 1);
    }

    public function getFormatPackagingCostAttribute()
    {
        return sprintf("%1\$.2f", $this->packaging_cost).' '. $this->currency;
    }

}
