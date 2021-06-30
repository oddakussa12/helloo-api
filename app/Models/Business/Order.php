<?php

namespace App\Models\Business;

use Illuminate\Database\Eloquent\Model;


class Order extends Model
{

    protected $table = "orders";

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    protected $fillable = ['user_id', 'shop_id' , 'user_name' , 'user_contact' , 'user_address' , 'detail' , 'address', 'order_price' , 'currency'];

    protected $hidden = ['schedule' , 'shop_price' , 'comment' , 'order_time' , 'operator' , 'deposit' , 'updated_at' , 'brokerage_percentage' , 'brokerage' , 'profit'];

    protected $appends = ['format_price'];

    protected $casts = [
        'user_id' => 'string',
        'shop_id' => 'string',
        'detail' => 'array',
        'order_price' => 'float',
    ];

    public function getFormatPriceAttribute()
    {
        return sprintf("%1\$.2f", $this->order_price).' '. $this->currency;
    }

}
