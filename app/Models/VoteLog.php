<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VoteLog extends Model
{

	const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    protected $primaryKey = 'id';

    protected $fillable = ['id', 'post_id', 'vote_id', 'user_id'];

    protected $table = 'vote_logs';

    public function setContentAttribute($value)
    {
        $this->attributes['content'] = htmlspecialchars_decode($value , ENT_QUOTES);
    }

}
