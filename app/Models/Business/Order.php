<?php

namespace App\Models\Business;

use Illuminate\Database\Eloquent\Model;


class Order extends Model
{

    protected $table = "orders";

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    protected $fillable = ['user_id', 'shop_id' , 'user_name' , 'user_contact' , 'user_address' , 'detail' , 'address', 'order_price' , 'currency' , 'discounted_price' , 'promo_price' , 'total_price'];

    protected $hidden = ['schedule' , 'shop_price' , 'comment' , 'order_time' , 'operator' , 'deposit' , 'updated_at' , 'brokerage_percentage' , 'brokerage' , 'profit'];

    protected $appends = ['format_price' , 'format_promo_price'];

    protected $casts = [
        'user_id' => 'string',
        'shop_id' => 'string',
        'detail' => 'array',
        'order_price' => 'float',
        'shop_price' => 'float',
        'delivery_coast' => 'float',
        'reduction' => 'float',
        'discount' => 'float',
        'discounted_price' => 'float',
        'deposit' => 'float',
        'brokerage_percentage' => 'float',
        'brokerage' => 'float',
        'profit' => 'float',
        'promo_price' => 'float',
        'total_price' => 'float',
    ];

    public function getFormatPriceAttribute()
    {
        return sprintf("%1\$.2f", $this->order_price).' '. $this->currency;
    }

    public function getFormatDiscountedPriceAttribute()
    {
        return sprintf("%1\$.2f", $this->discounted_price).' '. $this->currency;
    }

    public function getFormatPromoPriceAttribute()
    {
        return sprintf("%1\$.2f", $this->promo_price).' '. $this->currency;
    }

    public function getFormatTotalPriceAttribute()
    {
        return sprintf("%1\$.2f", $this->total_price).' '. $this->currency;
    }

}
