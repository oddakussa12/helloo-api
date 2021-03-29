<?php

namespace App\Traits;

use App\Models\UserScore;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

trait CacheableScore
{
    /**
     * @param $params
     * @param string $type 类型：like 等
     * @param string $user_id 需要加分的ID
     * @param string $friend_id 需要加分的ID
     * @param string $sourceType 来源：如 video / photo
     * @param $id table_id
     * @return bool
     * 增加积分
     *
     */
    public function addScore($params)
    {
        switch ($params['type']) {

            case "type": //点赞
                $this->like($params);
                break;
            case "addMedia": // 个人中心发布video / photo
                $this->addMedia($params);
                break;
            default:
                break;
        }

        return true;
    }

    /**
     * @param $params
     * 点赞
     */
    public function like($params, $score=1)
    {

        $detail['type']    = $params['type'];
        $detail['user_id'] = $params['user_id'];
        $detail['score']   = $score;
        $this->scoreDetail($params);

        $this->score($params['user_id'], 1);
    }

    /**
     * @param $params
     * 发布 Video/Photo
     */
    public function addMedia($params)
    {

        $this->scoreDetail($params);
        $this->score($params['user_id'], 1);

    }

    /**
     * @param $params
     * 插入积分明细表
     */
    public function scoreDetail($params)
    {
        // 接入积分明细表
        $hash  =
        $table = 'users_scores_log'.$hash;

        $params['created_at'] = date('Y-m-d H:i:s');
        DB::table($table)->insert($params);
    }

    /**
     * @param $user_id
     * @param $score
     * 积分总表
     */
    public function score($user_id, $score)
    {
        $totalScore = UserScore::where('user_id', $user_id)->first();
        if (empty($totalScore)) {
            $insert['user_id'] = $user_id;
            $insert['score']   = $score;
            UserScore::create($totalScore);
        } else {
            $totalScore->score += $score;
            $totalScore->save();
        }
        // Redis
        // Redis::set();
    }
}
