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
    /**
     * @var mixed
     */

    /**
     * 定义索引里的type值,重写
     * @return string
     */
    public function searcheableAs()
    {
        return "device";
    }

    /**
     * 定义那些字段需要搜索
     * @return array
     */
    public function searchableArray()
    {
        return [
            'device_language' => $this->device_language,
            'device_phone_model' => $this->device_phone_model
        ];
    }

}
