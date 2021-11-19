<?php

namespace App\Models;

use Carbon\Carbon;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Support\Facades\DB;
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
        'user_name',
        'user_gender' ,
        'user_birthday' ,
        'user_avatar' ,
        'user_bg' ,
        'user_src' ,
        'user_address',
        'user_about' ,
        'user_school' ,
        'user_sl' ,
        'user_grade' ,
        'user_contact',
        'user_verified' ,
        'user_verified_at' ,
        'user_answer' ,
        'user_activation' ,
        'user_activated_at' ,
        'user_enrollment_at' ,
        'user_delivery',
        'user_currency',

        'user_tag',
        'user_online',
        'user_timezone',
        'user_business_time',
        'food_preparation_time'
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
        'user_verified',
        'user_created_at',
        'user_name_change',
        'user_name_changed_at',
        'user_updated_at',
        'user_name_changed_at',
        'user_verified_at',
        'user_activated_at',
        'user_answered_at',

        'user_online',
        'user_timezone',
        'user_business_time',
    ];

    protected $casts = [
        'user_delivery' => 'boolean',
    ];

    public function getUserAvatarLinkAttribute()
    {
        return splitJointQnImageUrl($this->user_avatar);
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
    // shop/resturant i.e user has many orders
    public function orders(){
        return $this->hasMany('App\Models\Business\Order','shop_id','user_id')
        ->where('created_at', '>', now()->subDays(30)->endOfDay());
    }

    // Shop/resturnat has many goods
    public function avg_check(){
        return $this->hasMany('App\Models\Business\Order','shop_id','user_id')
        ->where('order_price', '!=', 0.00)
        ->select('*', DB::raw('AVG(order_price) AS avg_check'));
    }

    public function average_price(){
        return $this->hasMany('App\Models\Business\Order','shop_id','user_id')
        ->where('order_price', '!=', 0.00)
        ->where('order_price', '>=' , 40)
        ->where('order_price', '<=' , 3000)
        ->select('*', DB::raw('AVG(order_price) AS avg_check'));
    }



}
