<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Report extends Model
{

    protected $table = "reports";

    protected $fillable = ['user_id' ,  'reportable_id' , 'reportable_type'];

    public $paginateParamName = 'report_page';

    public function reportable()
    {
        return $this->morphTo();
    }

}
