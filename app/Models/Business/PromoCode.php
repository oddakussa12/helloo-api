<?php

namespace App\Models\Business;

use Illuminate\Database\Eloquent\Model;


class PromoCode extends Model
{

    protected $table = "promo_code";

    protected $primaryKey = "id";

    public $incrementing = false;

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'description',
        'promo_code' ,
        'free_delivery' ,
        'deadline' ,
        'discount_type' ,
        'reduction' ,
        'limit',
        'percentage',
        'created_at' ,
        'updated_at'
    ];

    protected $hidden = ['id' , 'created_at' , 'updated_at'];

    protected $casts = [
        'free_delivery' => 'boolean',
    ];

}
