<?php

namespace App\Models;

use App\Traits\CachablePost;
use Dimsav\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
use Jenssegers\Agent\Agent;


class VoteDetail extends Model
{
    use CachablePost;

    protected $table = "vote_details";

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    public $translatedAttributes = ['locale' , 'content'];

    protected $fillable = [
      'post_id','user_id','tab_name','vote_media','default_locale','country','vote_type','vote_num', 'content'
    ];

   // public $translationModel = 'App\Models\VoteDetailTranslation';

    public function voteDetailTranslate()
    {
        return $this->hasOne(VoteDetailTranslation::class, 'vote_detail_id' , 'id')->where('locale', locale());
    }
}
