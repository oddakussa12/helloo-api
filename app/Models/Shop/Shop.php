<?php

namespace App\Models\Shop;

use Illuminate\Database\Eloquent\Model;


class Shop extends Model
{

    protected $table = "shops";

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    protected $fillable = ['user_id', 'name' , 'avatar' , 'cover' , 'recommend' , 'nick_name' , 'address', 'phone', 'description'];

}
