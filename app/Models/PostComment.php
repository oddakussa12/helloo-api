<?php

namespace App\Models;

use Carbon\Carbon;
use Dimsav\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
use App\Traits\like\CanBeLiked;
use App\Traits\favorite\CanBeFavorited;
use Illuminate\Database\Eloquent\SoftDeletes;

class PostComment extends Model
{
    use Translatable,CanBeLiked,CanBeFavorited,SoftDeletes;

    protected $table = "posts_comments";

    const CREATED_AT = 'comment_created_at';

    const UPDATED_AT = 'comment_updated_at';

    const DELETED_AT = 'comment_deleted_at';

    protected $primaryKey = 'comment_id';

    public $translatedAttributes = ['comment_locale' , 'comment_content'];

    protected $fillable = [
        'comment_id' ,
        'post_id' ,
        'user_id' ,
        'comment_to_id' ,
        'comment_top_id' ,
        'comment_verify',
        'comment_image',
        'comment_comment_p_id',
        'comment_like_num',
        'comment_like_temp_num',
        'comment_default_locale' ,
        'comment_verified_at',
        'comment_country_id'
    ];

    public $translationModel = 'App\Models\PostCommentTranslation';

    public $paginateParamName = 'comment_page';

    protected $localeKey = 'comment_locale';

    public $perPage = 6;

    protected $with = [
//        'children'
    ];

//    public function comment()
//    {
//        return $this->hasMany('App\Models\PostComment' , 'comment_comment_p_id');
//    }
//
//    public function user()
//    {
//        return $this->belongsTo('App\Models\User' , 'user_id' , 'user_id');
//    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id' , 'user_id')->withDefault();
    }
    public function to()
    {
        return $this->belongsTo(User::class, 'comment_to_id' , 'user_id')->withDefault();
    }

    public function ownedBy(User $user)
    {
        return $this->user_id == $user->user_id;
    }
    public function getCommentOwnerAttribute()
    {
        if(auth()->check())
        {
            return $this->ownedBy(auth()->user());
        }
        return false;
    }



    public function children()
    {
        $children =  $this->hasMany(self::class, 'comment_comment_p_id')
            ->with('owner');
        if(auth()->check())
        {
            $children = $children->with(['likers'=>function($query){
                $query->where('users.user_id' , auth()->id());
            }]);
        }
        return $children->with('translations')
            ->orderBy('comment_like_num' , 'desc')
            ->orderBy($this->getCreatedAtColumn() , 'desc');
    }


    public function parent()
    {
        return $this->belongsTo(self::class, 'comment_comment_p_id');
    }

    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id' , 'post_id');
    }

    public function getCommentLikeStateAttribute()
    {
        if(auth()->check())
        {
            $likeBy = $this->isLikedBy(auth()->user());
            return empty($likeBy)?false:$likeBy->pivot->likable_state;
        }
        return false;
    }

    public function getCommentDecodeContentAttribute()
    {
        if(empty($this->comment_content))
        {
            $comment_content = optional($this->translate(config('translatable.translate_default_lang')))->comment_content;
            if(empty($comment_content))
            {
                $comment_content = optional($this->translate($this->comment_default_locale))->comment_content;
                if(empty($comment_content))
                {
                    $comment_content = optional($this->translate('en'))->comment_content;
                }
            }
        }else{
            $comment_content = $this->comment_content;
        }
//        return $comment_content;
        return htmlspecialchars_decode(htmlspecialchars_decode($comment_content , ENT_QUOTES) , ENT_QUOTES);
    }

    public function getCommentDefaultContentAttribute()
    {
        $comment_content = optional($this->translate($this->comment_default_locale))->comment_content;
//        return $comment_content;
        return htmlspecialchars_decode(htmlspecialchars_decode($comment_content , ENT_QUOTES) , ENT_QUOTES);
    }

    public function getCommentFormatCreatedAtAttribute()
    {
        $locale = locale();
        if($locale=='zh-CN')
        {
            Carbon::setLocale('zh');
        }elseif ($locale=='zh-TW'||$locale=='zh-HK')
        {
            Carbon::setLocale('zh_TW');
        }
        return Carbon::parse($this->comment_created_at)->diffForHumans();
    }

    public function getCommentImageAttribute($value)
    {
        if(empty($value))
        {
            return array();
        }
        $value = \json_decode($value,true);

        $comment_reource['comment_image'] = \array_map(function($v){
            return config('common.qnUploadDomain.thumbnail_domain').$v;
        },$value);
        $comment_reource['comment_thumb_image'] = \array_map(function($v){
            return config('common.qnUploadDomain.thumbnail_domain').$v.'?imageView2/5/w/192/h/192/interlace/1';
        },$value);
        return $value=$comment_reource;
    }

}
