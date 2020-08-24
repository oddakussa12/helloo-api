<?php

namespace App\Models;

use Carbon\Carbon;
use Jenssegers\Agent\Agent;
use App\Traits\tag\HasTags;
use App\Traits\CachablePost;
use App\Traits\like\CanBeLiked;
use App\Traits\dislike\CanBeDisliked;
use Dimsav\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
use App\Traits\favorite\CanBeFavorited;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use Translatable,CanBeLiked,CanBeDisliked,CanBeFavorited,SoftDeletes,HasTags,CachablePost;

    public $currentLocale = 'en';

    protected $table = "posts";

    const CREATED_AT = 'post_created_at';

    const UPDATED_AT = 'post_updated_at';

    const DELETED_AT = 'post_deleted_at';

    protected $primaryKey = 'post_id';

    public $translatedAttributes = ['post_locale' , 'post_title' , 'post_content'];

    protected $fillable = [
        'post_id' ,
        'post_uuid' ,
        'user_id' ,
        'post_category_id' ,
        'post_media',
        'post_category_id',
        'post_default_locale',
        'post_content_default_locale',
        'post_country_id',
        'post_event_country_id',
        'post_type',
        'post_rate',
        'post_topped_at' ,
        'post_topping'
    ];

    protected $casts = [
        'post_media' => 'array',
    ];

    public $translationModel = 'App\Models\PostTranslation';

    public $paginateParamName = 'post_page';

    protected $localeKey = 'post_locale';

    public $perPage = 8;

    public function comments()
    {
        return $this->hasMany(PostComment::class , 'post_id' , 'post_id');
    }

    public function view()
    {
        return $this->hasMany(PostView::class , 'post_id' , 'post_id');
    }

    public function viewCount()
    {
        return $this->hasOne(PostViewNum::class , 'post_id' , 'post_id')->withDefault(array(
            'post_view_num'=>0
        ));
    }

    public function user()
    {
        return $this->belongsTo(User::class , 'user_id' , 'user_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id' , 'user_id')->withDefault();
    }

    public function ownedBy(User $user)
    {
        return $this->user_id == $user->user_id;
    }

    public function reports()
    {
        return $this->morphMany(Report::class, 'reportable');
    }


    public function getPostOwnerAttribute()
    {
        if(auth()->check())
        {
            return $this->ownedBy(auth()->user());
        }
        return false;
    }

    public function getPostMutationMediaAttribute()
    {
        return postMedia($this->post_type, $this->post_media);
    }


    public function getPostLikeStateAttribute()
    {
        if(auth()->check())
        {
            $likeBy = $this->isLikedBy(auth()->user());
            return empty($likeBy)?false:true;
        }
        return false;
    }

    public function getPostDislikeStateAttribute()
    {
        if(auth()->check())
        {
            $likeBy = $this->isDislikedBy(auth()->user());
            return empty($likeBy)?false:true;
        }
        return false;
    }

    public function getPostFavoriteStateAttribute()
    {
        if(auth()->check())
        {
            return $this->isFavoritedBy(auth()->user());
        }
        return false;
    }

    public function getPostDecodeTitleAttribute()
    {
        if(empty($this->post_title))
        {
            $post_title = optional($this->translate(config('translatable.translate_default_lang')))->post_title;
            if(empty($post_title))
            {
                $post_title = optional($this->translate($this->post_default_locale))->post_title;
                if(empty($post_title))
                {
                    $post_title = optional($this->translate('en'))->post_title;
                }
            }
        }else{
            $post_title = $this->post_title;
        }
        return htmlspecialchars_decode(htmlspecialchars_decode($post_title , ENT_QUOTES) , ENT_QUOTES);
    }

    public function getPostDecodeContentAttribute()
    {
        if(empty($this->post_content))
        {
            $this->currentLocale = config('translatable.translate_default_lang');
            $post_content = optional($this->translate(config('translatable.translate_default_lang')))->post_content;
            if(empty($post_content))
            {
                $this->currentLocale = $this->post_content_default_locale;
                $post_content = optional($this->translate($this->post_content_default_locale))->post_content;
                if(empty($post_content))
                {
                    $this->currentLocale = 'en';
                    $post_content = optional($this->translate('en'))->post_content;
                }
            }
        }else{
            $this->currentLocale = locale();
            $post_content = $this->post_content;
        }
        $post_content = htmlspecialchars_decode(htmlspecialchars_decode($post_content , ENT_QUOTES) , ENT_QUOTES);
        $agent = new Agent();
        if($agent->match('YoouliOS')||$agent->match('YooulAndroid'))
        {
            $post_content = str_replace(
                array("<br>" , "<br/>" , "<br />" , "<br  />" , "<br >" , "< br>" , "<  br>" , "<br  >" , "<br/ >" , "<br/  >") ,
                "\n" , $post_content);
        }
        return $post_content;
    }

    public function getPostDefaultContentAttribute()
    {
        $post_content = optional($this->translate($this->post_content_default_locale))->post_content;
        $post_content =  htmlspecialchars_decode(htmlspecialchars_decode($post_content , ENT_QUOTES) , ENT_QUOTES);
        $agent = new Agent();
        if($agent->match('YoouliOS')||$agent->match('YooulAndroid'))
        {
            $post_content = str_replace(
                array("<br>" , "<br/>" , "<br />" , "<br  />" , "<br >" , "< br>" , "<  br>" , "<br  >" , "<br/ >" , "<br/  >") ,
                "\n" , $post_content);
        }
        return $post_content;


    }
    public function getPostDefaultTitleAttribute()
    {
        $post_title = optional($this->translate($this->post_default_locale))->post_title;
        return htmlspecialchars_decode(htmlspecialchars_decode($post_title , ENT_QUOTES) , ENT_QUOTES);
    }

    public function getPostIndexTitleAttribute()
    {
        $title = $this->post_decode_title;
        if(empty($title))
        {
            return str_limit(strip_tags($this->post_decode_content) , 120 , '...');
            //return str_limit_by_lang(strip_tags($this->post_decode_content) , $this->currentLocale , 120);
        }
        return $title;
    }

    public function getPostIndexLocaleAttribute()
    {
        if($this->post_decode_title)
        {
            return $this->post_default_locale;
        }else{
            return $this->post_content_default_locale;
        }

    }

    public function getPostOriginIndexTitleAttribute()
    {
        $title = $this->post_origin_title;
        if(empty($title))
        {
            return str_limit(strip_tags($this->post_origin_content) , 120 , '...');
        }
        return $title;
    }

    public function getPostOriginTitleAttribute()
    {
        $post_title = optional($this->translate($this->post_default_locale))->post_title;
        return htmlspecialchars_decode(htmlspecialchars_decode($post_title , ENT_QUOTES) , ENT_QUOTES);
    }

    public function getPostOriginContentAttribute()
    {
        $post_content = optional($this->translate($this->post_content_default_locale))->post_content;
        $post_content = htmlspecialchars_decode(htmlspecialchars_decode($post_content , ENT_QUOTES) , ENT_QUOTES);
        $agent = new Agent();
        if($agent->match('YoouliOS')||$agent->match('YooulAndroid'))
        {
            $post_content = str_replace(
                array("<br>" , "<br/>" , "<br />" , "<br  />" , "<br >" , "< br>" , "<  br>" , "<br  >" , "<br/ >" , "<br/  >") ,
                "\n" , $post_content);
        }
        return $post_content;
    }

    public function getPostRealRateAttribute()
    {
//        $top_rate = rate_comment(500 , '2019-10-31 23:59:59');
//        return round(($value/$top_rate)*100);
        return round($this->post_rate , 2)*100;
    }

    public function getPostFormatCreatedAtAttribute()
    {
        return dateTrans($this->post_created_at);
    }

    public function getFormatRateAttribute()
    {
        $rate = $this->post_rate;
        if($rate<10)
        {
            $rate = round($rate , 1);
        }else{
            $rate = round($rate);
        }
        return $rate;
    }

    public function getPostViewNumAttribute()
    {
        return $this->viewCount->post_view_num;
    }

    public function getFireRateAttribute()
    {
        $fire = $this->viewCount->post_view_num+$this->post_comment_num;
        if($fire>999)
        {
            return '1K+';
        }
        return $fire;
    }

    public function setPostEventCountryIdAttribute($value)
    {
        if(is_numeric($value))
        {
            $index = $value;
        }else{
            if($value=='world')
            {
                $index = 0;
            }else{
                $index = array_search(strtoupper($value) , config('countries'));
                if($index===false)
                {
                    $index = -1;
                }else{
                    $index = $index+1;
                }
            }
        }
        $this->attributes['post_event_country_id'] = $index;
    }

    public function getPostEventCountryAttribute()
    {
        return getCountryName($this->post_event_country_id, false);
    }

    public function calculatingRate()
    {
        $commenterNum = $this->commenterCount($this->getKey());
        $countryNum = $this->countryNum($this->getKey());
        $rate = rate_comment_v3($this->post_comment_num , $this->post_created_at, $this->post_like_num , $commenterNum , $countryNum);
        if($rate!=$this->post_rate)
        {
            $this->timestamps = false;
            $this->post_rate = $rate;
            $this->save();
        }
    }


}
