<?php

namespace App\Models;

use App\Traits\like\CanLike;
use Laravel\Scout\Searchable;
use App\Traits\follow\CanFollow;
use App\Traits\dislike\CanDislike;
use App\Traits\favorite\CanFavorite;
use App\Traits\follow\CanBeFollowed;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Support\Facades\Storage;
use App\Foundation\Auth\Passwords\CanResetPassword;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Fenos\Notifynder\Traits\NotifableLaravel53 as NotifableTrait;

class User extends Authenticatable implements JWTSubject
{
    use CanLike,CanDislike,NotifableTrait,CanFollow,CanBeFollowed,CanFavorite,CanResetPassword,Searchable;

    protected $primaryKey = 'user_id';

    const CREATED_AT = 'user_created_at';

    const UPDATED_AT = 'user_updated_at';

    const DELETED_AT = 'user_deleted_at';

    protected $guarded = [
//        'user_name' ,
//        'users_email' ,
        'user_ip_address' ,
    ];
    protected $appends = ['user_country'];

    protected $fillable = [
        'user_name' ,
        'user_uuid' ,
        'user_email' ,
        'user_pwd',
        'user_nick_name',
//        'user_first_name' ,
//        'user_last_name' ,
        'user_gender' ,
//        'user_email_code' ,
//        'user_device_id' ,
//        'user_language' ,
        'user_birthday' ,
        'user_avatar' ,
        'user_cover' ,
        'user_src' ,
        'user_country_id' ,
        'user_age' ,
        'user_about' ,
        'user_google' ,
        'user_facebook' ,
        'user_twitter' ,
        'user_instagram' ,
        'user_score',
//        'user_active' ,
//        'user_admin' ,
//        'user_verified' ,
        'user_is_guest' ,
//        'user_is_pro' ,
        'user_level' ,
        'user_ip_address' ,
        'user_profile_like_num' ,
        'user_picture' ,
    ];

    public $default_name_field = 'user_name';

    public $default_email_field = 'user_email';

    public $default_password_field = 'user_pwd';



    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'user_pwd',
        'user_first_name' ,
        'user_last_name' ,
        'user_email_code' ,
        'user_device_id' ,
        'user_language' ,
        'user_src' ,
        'user_country_id' ,
        'user_google' ,
        'user_facebook' ,
        'user_twitter' ,
        'user_instagram' ,
        'user_active' ,
//        'user_admin' ,
        'user_verified' ,
        'user_is_pro' ,
        'user_age_changed' ,
        'user_ip_address' ,
        'user_created_at',
        'user_updated_at',
        'user_deleted_at',
    ];

    public function searchableAs()
    {
        return '_doc';
    }
    //定义有哪些字段需要搜索
    public function toSearchableArray()
    {
        return [
            'user_id'           => $this->user_id,
            'user_name'         => $this->user_name,
            'user_nick_name'    => $this->user_nick_name  ?? '',
            'user_avatar'       => $this->user_avatar     ?? 'default_avatar.jpg',
            'user_country_id'   => $this->user_country_id ?? '',
            'user_gender'       => $this->user_gender     ?? -1,
            'user_about'        => $this->user_about      ?? '',
            'user_level'        => $this->user_level      ?? 0,
            'user_birthday'     => $this->user_birthday   ?? '',
        ];
    }

    //队列相关
    public function syncWithSearchUsingQueue()
    {
        return config('scout.scout_queue');
    }

    public function syncWithSearchUsing()
    {
        return config('scout.connection');
    }


    public function getUserAvatarLinkAttribute()
    {
        return userCover($this->user_avatar);
    }

    public function getUserCoverLinkAttribute()
    {
        return userCover($this->user_cover, 'cover');
    }

    public function getUserPictureLinkAttribute($value)
    {
        $value = $this->user_picture;
        $value = \json_decode($value , true);
        $value = $value===null?array():$value;
        return \array_map(function($v){
            return config('common.qnUploadDomain.avatar_domain').$v.'?imageMogr2/auto-orient/interlace/1|imageslim';
        } , $value);

    }

    public function getUserAboutAttribute($value)
    {
        return strval($value);
    }

//    public function videoViews()
//    {
//        return $this->hasMany('App\Models\Content\VideoView' , 'video_view_user_id' , 'user_id');
//    }


    // Rest omitted for brevity

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier() {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims() {
        return [];
    }

    public function getAuthPassword() {
        $default_passwrod = $this->default_password_field;
        return $this->$default_passwrod;
    }

    public function setUserPwdAttribute($value)
    {
        $this->attributes[$this->default_password_field] = bcrypt($value);
    }

    public function SignupInfo()
    {
        return $this->hasOne(SignupInfo::class , 'user_id' , 'user_id');
    }

    public function yesterdayScore()
    {
        return $this->hasOne(YesterdayScore::class , 'user_id' , 'user_id');
    }

    public function reports()
    {
        return $this->hasMany(Report::class, 'user_id', $this->getKeyName());
    }

    public function posts()
    {
        return $this->hasMany('App\Models\Post' , 'user_id' , 'user_id');
    }

    public function getUserCountryAttribute()
    {
        return getUserCountryName($this->user_country_id);
    }

    public function setUserCountryIdAttribute($value)
    {
        $index = array_search(strtoupper($value) , config('countries'));
        if($index===false)
        {
            $index = 236;
        }else{
            $index = $index+1;
        }
        $this->attributes['user_country_id'] = $index;
    }

    public function calculatingScore($score)
    {
        $this->timestamps = false;
        $this->user_score = $score;
        $this->save();
    }

    public function profileLike()
    {
        return $this->hasOne(UserProfileLike::class , 'user_id' , 'user_id');
    }

    public function tags(): MorphToMany
    {
        return $this
            ->morphToMany(UserTag::class, 'taggable' , 'users_taggables' , 'taggable_id' , 'tag_id')
            ->orderBy('tag_sort');
    }

    public function getUserTagsAttribute()
    {
        $userTags = Storage::get('userTags/tags.json');
        $userTags = \json_decode($userTags , true);
        $tags = UserTaggable::where('taggable_id' , $this->getKey())->where('taggable_type' , self::class)->get();
        $tags->each(function($tag , $index) use ($userTags){
            $tag->tag_slug = $userTags[$tag->tag_id]['tag_slug'];
        });
        $tags = $tags->filter(function ($tag, $index) {
            return !blank($tag->tag_slug);
        });
        return $tags;
    }

    public function regions()
    {
        return $this->belongsToMany(Region::class,'users_regions' , 'user_id' , 'region_id');
    }

    public function getUserRegionsAttribute()
    {
        $userRegions = config('user-region');
        $regions = UserRegion::where('user_id' , $this->getKey())->get();
        $regions->each(function($region , $index) use ($userRegions){
            $region->region_slug = $userRegions[intval($region->region_id)]??'';
        });
        $regions = $regions->filter(function ($region, $index) {
            return !blank($region->region_slug );
        });
        return $regions;
    }

    public function setUserGenderAttribute($value)
    {
        $value = intval($value);
        $value = $value==1?$value:0;
        $this->attributes['user_gender'] = $value;
    }

    public function getUserContinentAttribute()
    {
        return getContinentByCountry($this->user_country);
    }




}
