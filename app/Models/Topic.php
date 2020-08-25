<?php

namespace App\Models;

use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\Model;

class Topic extends Model
{
    use Searchable;

    public $guarded = [];
    protected $primaryKey = 'topic_id';

    public $table = 'posts_topics';

    protected $dateFormat = 'U';

    public const CREATED_AT = "topic_created_at";

    public const UPDATED_AT = "topic_updated_at";

}
