<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CountryCode extends Model
{
    protected $table = "country_cods";
    protected $fillable = [
        'code','name','icon','areaCode'
    ];
}
