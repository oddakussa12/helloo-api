<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;


class Report extends Model
{
    use Searchable;

    protected $table = "reports";

    protected $fillable = ['user_id' ,  'reportable_id' , 'reportable_type'];

    public $paginateParamName = 'report_page';

    public function reportable()
    {
        return $this->morphTo();
    }

}
