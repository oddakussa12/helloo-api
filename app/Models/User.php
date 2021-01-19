<?php

namespace App\Models;

use Carbon\Carbon;
use Tymon\JWTAuth\Contracts\JWTSubject;
use App\Foundation\Auth\Passwords\CanResetPassword;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements JWTSubject
{
    use CanResetPassword;

    protected $primaryKey = 'user_id';

    const CREATED_AT = 'user_created_at';

    const UPDATED_AT = 'user_updated_at';

    const DELETED_AT = 'user_deleted_at';

    protected $guarded = [
//        'user_name' ,
//        'users_email' ,
        'user_ip_address' ,
    ];
    protected $appends = ['user_avatar_link'];

    protected $fillable = [
        'user_uuid' ,
        'user_pwd',
        'user_nick_name',
//        'user_name',
        'user_gender' ,
        'user_birthday' ,
        'user_avatar' ,
        'user_src' ,

        'user_about' ,
        'user_school' ,
        'user_grade' ,

        'user_answer' ,
        'user_activation' ,
        'user_activated_at' ,
        'user_enrollment_at' ,
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
        'user_src' ,
        'user_avatar',
        'user_created_at',
        'user_name_change',
        'user_name_changed_at',
        'user_updated_at',
        'user_activated_at',
        'user_answered_at'
    ];



    public function getUserAvatarLinkAttribute()
    {
        return userCover($this->user_avatar);
    }


    public function getUserAboutAttribute($value)
    {
        return strval($value);
    }


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

    public function setUserActivationAttribute($value)
    {
        if($this->user_activation==0)
        {
            $this->attributes['user_activation'] = 1;
            $this->attributes['user_activated_at'] = Carbon::now()->toDateTimeString();
        }
    }
    public function setUserPwdAttribute($value)
    {
        $this->attributes[$this->default_password_field] = bcrypt($value);
    }

//
//    public function getUserCountryAttribute()
//    {
//        return getUserCountryName($this->user_country_id);
//    }

    public function setUserCountryIdAttribute($value)
    {
        $this->attributes['user_country'] = strtolower($value);
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

    public function SignupInfo()
    {
        return $this->hasOne(SignupInfo::class , 'user_id' , 'user_id');
    }





}
