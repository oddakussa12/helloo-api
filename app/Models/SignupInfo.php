<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SignupInfo extends Model
{
    //
    public $timestamps = false;

    protected $primaryKey = 'signup_id';

    protected $fillable = [
        'user_id' ,
        'signup_device' ,
        'device_type',
        'signup_browser',
        'signup_browser_version',
        'signup_platform',
        'signup_platform_version',
        'signup_ip',
        'signup_lang',
        'signup_isocode',
        'signup_country',
        'signup_state',
        'signup_city',
        'signup_lat',
        'signup_lon',
        'signup_timezone',
        'signup_continent',
    ];
}
