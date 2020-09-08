<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserTaggable extends Model
{
    public $table = "users_taggables";

    public $fillable = array(
        "taggable_id",
        "tag_id",
        "taggable_type",
    );

    public $timestamps = false;
}
