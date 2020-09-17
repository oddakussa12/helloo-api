<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class BlackUser extends Model
{

    protected $table = "black_users";

    protected $primaryKey = 'id';

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'update_at';

    public function setUpdatedAtAttribute($value) {
        // Do nothing.
    }

    public $dateFormat = "U";


    protected $fillable = [
        'user_id',
        'desc',
        'operator',
        'is_delete',
        'created_at'
    ];

    /**
     * @param $black_user_id 被封禁ID
     * @param string $operator 执行人
     * @param string $desc 封禁描述
     * @return bool
     * 插入封号表
     */
    public static function findOrInsert($black_user_id, $operator='', $desc='')
    {
        $params = [
            'user_id'   => $black_user_id,
            'operator'  => $operator,
            'desc'      => $desc,
            'is_delete' => 0,
        ];
        $block = BlackUser::where($params)->where('is_delete', 0)->first();
        if (empty($block)) {
            BlackUser::create($params);
        }
        return true;
    }
}
