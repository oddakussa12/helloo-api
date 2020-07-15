<?php

namespace App\Models;

use App\Traits\like\CanLike;
use App\Traits\follow\CanFollow;
use App\Traits\dislike\CanDislike;
use App\Traits\favorite\CanFavorite;
use App\Traits\follow\CanBeFollowed;
use Tymon\JWTAuth\Contracts\JWTSubject;
use App\Foundation\Auth\Passwords\CanResetPassword;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Fenos\Notifynder\Traits\NotifableLaravel53 as NotifableTrait;

class User extends Authenticatable implements JWTSubject
{
    use CanLike,CanDislike,NotifableTrait,CanFollow,CanBeFollowed,CanFavorite,CanResetPassword;

    protected $primaryKey = 'user_id';

    const CREATED_AT = 'user_created_at';

    const UPDATED_AT = 'user_updated_at';

    const DELETED_AT = 'user_deleted_at';

    protected $guarded = [
        'user_name' ,
        'users_email' ,
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
        'user_email_code' ,
        'user_device_id' ,
        'user_language' ,
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
        'user_active' ,
        'user_admin' ,
        'user_verified' ,
        'user_is_guest' ,
        'user_is_pro' ,
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
//        'user_first_name' ,
//        'user_last_name' ,
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
        'user_admin' ,
        'user_verified' ,
        'user_is_pro' ,
        'user_age_changed' ,
        'user_ip_address' ,
        'user_created_at',
        'user_updated_at',
        'user_deleted_at',
    ];

    public function getUserAvatarLinkAttribute()
    {
        $value = $this->user_avatar;
        $value = empty($value)?'userdefalutavatar.jpg':$value;
        if (preg_match("/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i",$value)) {
            return $value;
        }
        return config('common.qnUploadDomain.avatar_domain').$value.'?imageView2/0/w/50/h/50/interlace/1|imageslim';
    }
    public function getUserCoverLinkAttribute()
    {
        $value = $this->user_cover;
        $value = empty($value)?'userdefalutcover.jpg':$value;
        if (preg_match("/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i",$value)) {
            return $value;
        }
        return config('common.qnUploadDomain.avatar_domain').$value.'?imageView2/0/w/50/h/50/interlace/1|imageslim';
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
        $country_code = config('countries');
        $country = ($this->user_country_id-1);
        if(array_key_exists($country , $country_code))
        {
            return strtolower($country_code[$country]);
        }
        return strtolower($country_code[235]);
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

    public function regions()
    {
        return $this->belongsToMany(Region::class,'users_regions' , 'user_id' , 'region_id');
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
