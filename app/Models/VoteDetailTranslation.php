<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VoteDetailTranslation extends Model
{

	const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    protected $primaryKey = 'id';

    protected $fillable = ['id', 'post_id', 'vote_detail_id', 'locale','content'];

    protected $table = 'vote_details_translations';

    public function setContentAttribute($value)
    {
        $this->attributes['content'] = htmlspecialchars_decode($value , ENT_QUOTES);
    }

}
