<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Block extends Model
{

    protected $table = "blocks";

    protected $primaryKey = 'id';

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    public function setUpdatedAtAttribute($value) {
        // Do nothing.
    }

    public $dateFormat = "U";


    protected $fillable = [
        'user_id',
        'block_user_id',
        'post_uuid',
        'is_delete',
        'created_at'
    ];

    /**
     * @param $user_id
     * @param $block_user_id
     * @param string $post_uuid
     * @return bool
     * 屏蔽用户、帖子
     */
    public static function findOrInsert($user_id, $block_user_id, $post_uuid='')
    {
        $params = [
            'user_id'       => $user_id,
            'block_user_id' => $block_user_id,
            'post_uuid'     => $post_uuid ?? null,
            'is_delete'     => 0,
        ];
        $block = Block::where($params)->first();
        if (empty($block)) {
           $result = Block::create($params);
        }
        return $result ?? true;
    }
}
