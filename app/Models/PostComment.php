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
        'comment_verify',
        'comment_comment_p_id',
        'comment_like_num',
        'comment_default_locale' ,
        'comment_verified_at',
        'comment_country_id'
    ];

    public $translationModel = 'App\Models\PostCommentTranslation';

    public $paginateParamName = 'comment_page';

    protected $localeKey = 'comment_locale';

    protected $perPage = 10;

//    public function comment()
//    {
//        return $this->hasMany('App\Models\PostComment' , 'comment_comment_p_id');
//    }
//
    public function user()
    {
        return $this->belongsTo('App\Models\User' , 'user_id' , 'user_id');
    }

    public function owner()
    {
        return $this->belongsTo('App\Models\User', 'user_id' , 'user_id')->withDefault();
    }

    public function ownedBy(User $user)
    {
        return $this->user_id == $user->user_id;
    }

    public function newCollection(array $models = [])
    {
        return new CommentCollection($models);
    }

    public function children()
    {
        return $this->hasMany(self::class, 'comment_comment_p_id');
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
        if(auth()->check()&&$this->isLikedBy(auth()->user()))
        {
            return $this->isLikedBy(auth()->user())->likable_state;
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
        return strip_tags(htmlspecialchars_decode($comment_content , ENT_QUOTES));
    }

    public function getCommentDefaultContentAttribute()
    {
        $comment_content = optional($this->translate($this->comment_default_locale))->comment_content;
        return strip_tags(htmlspecialchars_decode($comment_content , ENT_QUOTES));
    }

    public function getCommentFormatCreatedAtAttribute()
    {
        return Carbon::parse($this->comment_created_at)->diffForHumans();
    }


}
