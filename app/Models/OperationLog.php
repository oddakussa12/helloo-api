<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;


class OperationLog extends Model
{

    protected $table = "views_logs";

    protected $primaryKey = 'id';
    
    protected $fillable = ['user_id' , 'ip' , 'referer'];

    public $paginateParamName = 'view_page';

    const CREATED_AT = 'created_at';

    public function setUpdatedAt($value)
    {
    }
    public function setCreatedAt($value)
    {
        $this->{static::CREATED_AT} = Carbon::now('Asia/Shanghai');

        return $this;
    }

}
