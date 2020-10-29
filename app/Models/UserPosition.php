<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPosition extends Model
{
    protected $table = "users_positions";

    const CREATED_AT = 'created_at';

    public $dateFormat = "U";

    protected $primaryKey = 'id';

    protected $fillable=['user_id', 'longitude', 'latitude'];

    public function setUpdatedAt($value)
    {

    }
}