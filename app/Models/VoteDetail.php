<?php

namespace App\Models;

use App\Traits\CachablePost;
use Dimsav\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
use Jenssegers\Agent\Agent;


class VoteDetail extends Model
{
    use Translatable,CachablePost;

    protected $table = "vote_details";

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    public $translatedAttributes = ['locale' , 'content'];

    protected $fillable = [
      'post_id','user_id','tab_name','vote_media','default_locale','country','vote_type','vote_num'
    ];

   // public $translationModel = 'App\Models\VoteDetailTranslation';
}
