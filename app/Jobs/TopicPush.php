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

    public function __construct(Post $post , $topics)
    {
        $this->post = $post;
        $this->topics = $topics;
        Log::info('message:::::批量推送给关注者  __construct ');
        $this->handle();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info('message:::::批量推送给关注者  handle ');
        Log::info('message::::发布话题 handle  start');
        $result = DB::table('posts_topics')->select(DB::raw("count(1) as num"), 'topic_content')
                  ->whereIn('topic_content', $this->topics)->groupBy('topic_content')->get()->toArray();

        $topicList = array_map(function ($v) {
            if ($v->num >= Constant::TOPIC_PUSH_THRESHOLD) return $v->topic_content;
        }, $result);

        $data   = [];
        foreach ($topicList as $item) {
            DB::table('topics_follows')->where('topic_content', $item)->chunk(50, function ($users) use ($data, $item) {
                    $userIds = $users->pluck('user_id')->all();
                    if (!empty(count($userIds))) {
                        $languages = $this->getDeviceList($userIds);
                        $userNickName = '';
                        foreach ($languages as $language => $datum) {
                            if (!empty($datum['device'])) {
                                Mpush::dispatch('publish_topic', $userNickName, $language, (object)$datum['device'], $item)->onQueue(Constant::QUEUE_PUSH_NAME);
                            }
                        }
                    }
                });
        }
        Log::info('message::::发布话题 handle  end');


    }

    /**
     * @param $userIds
     * @return array
     * 通过userIds获取用户设备信息
     */
    public static function getDeviceList($userIds)
    {
        $devices = \App\Models\Device::whereIn('user_id', $userIds)->where(['device_register_type'=>'fcm'])
            ->groupBy('user_id')->orderBy('device_updated_at', 'DESC')->get()->toArray();
        $push    = ['device_register_type' => 'fcm', 'device_type' => 2];

        $languages = array_column($devices, 'device_language');

        foreach ($languages as $k=>$language) {
            foreach ($devices as $key =>$item) {
                if (!in_array($item['device_language'], $languages)) {
                    $data['en']['device'] = $push;
                    $data['en']['device']['device_registration_id'][] = $item['device_registration_id'];
                } else {
                    if ($language == $item['device_language']) {
                        $data[$language]['device'] = $push;
                        $data[$language]['device']['device_registration_id'][] = $item['device_registration_id'];
                    }
                }
            }
        }

        return $data ?? [];

    }
}
