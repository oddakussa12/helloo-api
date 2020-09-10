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
        'is_delete',
    ];

    /**
     * @param $user_id
     * @param $block_user_id
     * @param string $post_id
     * @return bool
     * 屏蔽用户、帖子
     */
    public static function findOrInsert($user_id, $block_user_id, $post_id='')
    {
        $params = [
            'user_id'       => $user_id,
            'block_user_id' => $block_user_id,
            'post_id'       => $post_id ?? null,
            'is_delete'     => 0,
        ];
        $block = Block::where($params)->where('is_delete', 0)->first();
        if (empty($block)) {
            Block::create($params);
        }
        return true;
    }
}
