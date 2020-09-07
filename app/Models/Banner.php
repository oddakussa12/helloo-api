<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Banner extends Model
{

    protected $table = "banners";

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'update_at';

    public function setUpdatedAtAttribute($value) {
        // Do nothing.
    }

    public $dateFormat = "U";

}
