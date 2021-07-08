<?php

namespace App\Models\Business;

use Illuminate\Database\Eloquent\Model;


class ShopTag extends Model
{
    protected $table = "shops_tags";

    protected $primaryKey = "id";

    public $incrementing = false;

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    protected $fillable = ['tag' , 'created_at'];

    protected $hidden = ['id' , 'created_at' , 'updated_at'];

}
