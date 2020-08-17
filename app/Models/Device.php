<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Device extends Model
{
    use Searchable;

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
        'user_id', 'device_registration_id'
        //'device_language','user_id', 'device_registration_id', 'device_phone_model', 'device_country'
    ];

    protected $guarded=[]; //不可以注入的字段

    /**
     * 定义索引里的type值,重写
     * // 定义索引里面的类型

     * @return string
     */
    public function searchableAs()
    {
        return "device";
    }

    /**
     * 定义那些字段需要搜索
     * @return array
     */
    public function toSearchableArray()
    {
      return array_only($this->toArray(), ['user_id', 'device_registration_id']);

    }

}
