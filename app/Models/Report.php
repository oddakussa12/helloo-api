<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Report extends Model
{

    protected $table = "reports";

    protected $fillable = ['user_id' ,  'reported_id'];

    public $paginateParamName = 'report_page';


}
