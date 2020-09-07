<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserRegion extends Model
{
    public $table = "users_regions";

    public $fillable = array(
        "user_id",
        "region_id",
    );

    public $timestamps = false;
}
