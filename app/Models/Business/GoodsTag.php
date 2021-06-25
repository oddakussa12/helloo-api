<?php

namespace App\Models\Business;

use Illuminate\Database\Eloquent\Model;


class GoodsTag extends Model
{
    protected $table = "goods_tags";

    protected $primaryKey = "id";

    public $incrementing = false;

    const CREATED_AT = 'created_at';

    protected $fillable = ['tag' , 'created_at'];

    protected $hidden = ['created_at'];

}
