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
        return;
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
     * @param int $score
     * 点赞
     */
    public function like($params, $score=1)
    {
        // 自己加积分
        $this->scoreDetail($params['type'], $params['user_id'], $params['id'], $score);
        // 对方加积分
        $this->scoreDetail($params['type'], $params['friend_id'], $params['id'], $score);

        // 总积分
        $this->score($params['user_id'], 1);
    }

    /**
     * @param $params
     * 发布 Video/Photo
     */
    public function addMedia($params)
    {
        return;
        // 积分历史
        $score = $params['sourceType'] == 'video' ? 5 : 2;
        $this->scoreDetail($params['type'], $params['user_id'], $params['id'], $score);

        //添加总积分
        $this->score($params['user_id'], 1);
    }

    /**
     * @param $params
     * 删除Video、Photo
     */
    public function delMedia($params)
    {
        return;
        // 积分历史
        $score = $params['sourceType'] == 'video' ? -5 : -2;
        $this->scoreDetail($params['type'], $params['user_id'], $params['id'], $score);

        //添加总积分
        $this->score($params['user_id'], 1);

    }

    /**
     * @param $type
     * @param $userId
     * @param $id
     * @param $score
     */
    public function scoreDetail($type, $userId, $id, $score)
    {
        // 接入积分明细表
        $table = 'users_scores_logs_'.hashDbIndex($userId);

        // 积分历史
        $detail['type']     = $type;
        $detail['user_id']  = $userId;
        $detail['relation'] = $id;
        $detail['score']    = $score;
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
