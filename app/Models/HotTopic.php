<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HotTopic extends Model
{

    public $guarded = [];

    protected $primaryKey = 'id';

    public $table = 'hot_topics';

    protected $dateFormat = 'U';

    public const CREATED_AT = "created_at";

    public const UPDATED_AT = "updated_at";



}
