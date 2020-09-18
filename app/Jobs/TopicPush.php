<?php

namespace App\Jobs;

use App\Custom\Constant\Constant;
use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

/**
 * Class TopicPush
 * @package App\Jobs
 * 当发送的话题达到某个阈值时，推送给关注该话题的用户
 */
class TopicPush implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $topics;
    private $post;

    public function __construct(Post $post, $topics)
    {
        $this->post = $post;
        $this->topics = $topics;

    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $result = DB::table('posts_topics')->select(DB::raw("count(1) as num"), 'topic_content')
                  ->whereIn('topic_content', $this->topics)->groupBy('topic_content')->get()->toArray();

        $topicList = array_map(function ($v) {
            if ($v->num % Constant::TOPIC_PUSH_THRESHOLD == 0) return $v->topic_content;
        }, $result);
        $topicList = array_filter($topicList);
        $data      = [];
        
        if ($topicList) {
            foreach ($topicList as $item) {
                DB::table('topics_follows')->where('topic_content', $item)->orderByDesc('id')
                    ->chunk(50, function ($users) use ($data, $item) {
                        $userIds = $users->pluck('user_id')->all();
                        if (!empty($userIds)) {
                            $languages = $this->getDeviceList($userIds);
                            $userNickName = '';
                            foreach ($languages as $language => $datum) {
                                if (!empty($datum['device']) && !empty($datum['device']['device_registration_id'])) {
                                    Mpush::dispatch('publish_topic', $userNickName, $language, (object)$datum['device'], $item)->onQueue(Constant::QUEUE_PUSH_NAME);
                                }
                            }
                        }
                    });
            }
        }

    }

    /**
     * @param $userIds
     * @return array
     * 通过userIds获取用户设备信息
     */
    public static function getDeviceList($userIds)
    {
        $userIds = is_array($userIds) ? implode(',', $userIds) : $userIds;
        $sql     = "SELECT * from (SELECT user_id,device_registration_id,device_language,device_register_type from f_devices where user_id in ({$userIds}) ORDER BY device_updated_at desc) a GROUP BY user_id";
        $devices = collect(\DB::select($sql))->map(function ($value) {
            return (array)$value;
        })->toArray();

        $push      = ['device_register_type' => 'fcm', 'device_type' => 2, 'device_registration_id'=>[]];
        $languages = config('translatable.locales');
        $languages = !empty($languages) ? $languages : array_column($devices, 'device_language');

        foreach ($languages as $k=>$language) {
            $data[$language]['device'] = $push;
            foreach ($devices as $key =>$item) {
                if ($item['device_register_type']=='fcm') {
                    if (!in_array($item['device_language'], $languages)) {
                        $data['en']['device']['device_registration_id'][] = $item['device_registration_id'];
                    } else {
                        if ($language == $item['device_language']) {
                            $data[$language]['device']['device_registration_id'][] = $item['device_registration_id'];
                        }
                    }
                }
            }
        }

        return $data ?? [];

    }
}
