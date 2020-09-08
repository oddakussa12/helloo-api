<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Topic extends Model
{

    public $guarded = [];
    protected $primaryKey = 'topic_id';

    public $table = 'posts_topics';

    protected $dateFormat = 'U';

    public const CREATED_AT = "topic_created_at";

    public const UPDATED_AT = "topic_updated_at";

}
