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
      'post_id','user_id','tab_name','vote_media','default_locale','vote_type','vote_num'
    ];

   // public $translationModel = 'App\Models\VoteDetailTranslation';

    public function getContentAttribute()
    {
        if (empty($this->content)) {
            $this->currentLocale = config('translatable.translate_default_lang');
            $content             = optional($this->translate(config('translatable.translate_default_lang')))->content;
            if (empty($content)) {
                $this->currentLocale = $this->default_locale;
                $content = optional($this->translate($this->default_locale))->content;
                if(empty($content)) {
                    $this->currentLocale = 'en';
                    $content = optional($this->translate('en'))->content;
                }
            }
        } else {
            $this->currentLocale = locale();
            $content = $this->content;
        }
        $content = htmlspecialchars_decode(htmlspecialchars_decode($content , ENT_QUOTES) , ENT_QUOTES);
        $agent   = new Agent();
        if ($agent->match('YoouliOS')||$agent->match('YooulAndroid')) {
            $content = str_replace(
                array("<br>" , "<br/>" , "<br />" , "<br  />" , "<br >" , "< br>" , "<  br>" , "<br  >" , "<br/ >" , "<br/  >") ,
                "\n" , $content);
        }
        return $content;
    }
}
