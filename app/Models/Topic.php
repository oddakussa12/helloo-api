<?php

namespace App\Models;

use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\Model;

class Topic extends Model
{
    use Searchable;

    public $guarded = [];
    protected $primaryKey = 'topic_id';

    public $table = 'topics';

}
