<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Feedback extends Model
{

    protected $table = "feedback";

    const CREATED_AT = 'feedback_created_at';

    const DELETED_AT = 'feedback_deleted_at';

    protected $primaryKey = 'feedback_id';

    protected $fillable = ['feedback_id' , 'feedback_name' , 'feedback_email','feedback_content','feedback_created_at','feedback_deleted_at'];

    public $paginateParamName = 'feedback_page';

    public function setUpdatedAt($value)
    {

    }

}
