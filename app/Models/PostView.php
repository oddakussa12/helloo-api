<?php

namespace App\Models;

use App\Events\PostViewCreated;
use Illuminate\Database\Eloquent\Model;

class PostView extends Model
{
    protected $table = "posts_views";

    const CREATED_AT = 'post_view_created_at';

    protected $primaryKey = 'post_view_id';

    protected $fillable = [
        'post_view_id' ,
        'post_id' ,
        'user_id' ,
        'view_country' ,
        'view_state' ,
        'view_city' ,
        'post_view_ip'
    ];

    public $paginateParamName = 'view_page';

    public function setUpdatedAtAttribute($value) {
        // Do nothing.
    }

    protected $dispatchesEvents = [
        'created' => PostViewCreated::class,
    ];
}
