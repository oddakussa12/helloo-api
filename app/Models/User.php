<?php

namespace App\Models;

use Fenos\Notifynder\Traits\NotifableLaravel53 as NotifableTrait;
use App\Traits\like\CanLike;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Traits\CanFollow;
use App\Traits\CanFavorite;
use Overtrue\LaravelFollow\Traits\CanBeFollowed;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable,CanLike,NotifableTrait,CanFollow,CanBeFollowed,CanFavorite;

    protected $primaryKey = 'user_id';

    const CREATED_AT = 'user_created_at';

    const UPDATED_AT = 'user_updated_at';

    const DELETED_AT = 'user_deleted_at';

    protected $guarded = [
        'user_name' ,
        'users_email' ,
        'user_ip_address' ,
    ];

    protected $fillable = [
        'user_name' ,
        'user_uuid' ,
        'user_email' ,
        'user_pwd',
        'user_first_name' ,
        'user_last_name' ,
        'user_gender' ,
        'user_email_code' ,
        'user_device_id' ,
        'user_language' ,
        'user_avatar' ,
        'user_src' ,
        'user_country_id' ,
        'user_age' ,
        'user_about' ,
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
        // 'user_id',
        'user_pwd',
        'user_active',
        'user_is_pro',
        'user_verified',
        'user_age_changed',
        'user_email_code',
        'user_ip_address',
        'user_created_at',
        'user_updated_at',
        'user_deleted_at',
    ];

    public function getUserAvatarAttribute($value)
    {
        return config('common.qnUploadDomain.avatar_domain').$value.'?imageView2/0/w/100/h/100/interlace/1';
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
        return $this->hasOne('App\Models\SignupInfo' , 'user_id' , 'user_id');
    }

    public function likePost()
    {
        return $this->belongsToMany(Post::class , 'posts_likes' , 'user_id' , 'post_id')->withPivot('post_like_state');
    }

    public function posts()
    {
        return $this->hasMany('App\Models\Post' , 'user_id' , 'user_id');
    }

    public function getUserCountryAttribute()
    {
        $country_code = config('countries');
        $country = $this->user_country_id;
        if(array_key_exists($country , $country_code))
        {
            return $country_code[$country];
        }
        return $country_code[208];
    }






}
