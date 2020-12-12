<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{

    protected $table = "devices";

    const CREATED_AT = 'device_created_at';

    const UPDATED_AT = 'device_updated_at';
    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     * 可以注入的数据字段
     * @var array
     */
    protected $fillable = [
        'user_id',
        'device_registration_id',
        'device_language',
        'device_type',
        'device_phone_model',
        'device_country',
        'device_register_type'
    ];

    protected $guarded=[]; //不可以注入的字段
}
