<?php

namespace App\Models;

use App\Traits\tag\HasSlug;
use Spatie\EloquentSortable\Sortable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Spatie\EloquentSortable\SortableTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Collection as DbCollection;

class UserVisitLog extends Model
{

    public $primaryKey = 'id';

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

//    public $sortable = [
//        'order_column_name' => 'tag_sort',
//        'sort_when_creating' => true,
//    ];

    public $guarded = [];

    public $table = 'users_visit_log';

}
