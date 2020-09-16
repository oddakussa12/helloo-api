<?php

namespace App\Jobs;

use App\Custom\Constant\Constant;
use App\Models\User;
use App\Models\Post;
use App\Traits\CachableUser;
use App\Traits\CachablePost;
use Illuminate\Bus\Queueable;
use Illuminate\Config\Repository;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PostFans implements ShouldQueue
{
    use CachablePost,CachableUser,DispatchesJobs, InteractsWithQueue, Queueable, SerializesModels;

    private $languages;

    private $translate;

    protected $user;
    protected $post;
    /**
     * @var Repository
     */
    private $locales;
    private $postData;

    /**
     * Create a new job instance.
     *
     * @param User $user
     * @param Post $post
     * @param $postData
     *
     * 发送推送通知给自己所属的粉丝
     */
    public function __construct(User $user , Post $post, $postData)
    {
        $this->languages = config('translatable.locales');
        $this->user      = $user;
        $this->post      = $post;
        $this->postData  = $postData;
        Log::info('message::批量推送给粉丝  __construct ');
        $this->handle();
    }
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info('message::批量推送给粉丝  handle start ');
        $post_uuid = $this->post->post_uuid->toString();
        if (empty($post_uuid)) return false;
        $userId = $this->user->user_id ?? '';
        if (empty($userId)) return false;
        DB::table('common_follows')->where('followable_id', $userId)->where('followable_type' , User::class)
            ->where('relation' , 'follow')->orderByDesc('id')->chunk(50, function ($users) use($post_uuid) {
                $userIds = $users->pluck('user_id')->all();
                if (!empty(count($userIds))) {
                    $data = TopicPush::getDeviceList($userIds);
                    $userNickName = $this->user->user_nick_name ?? ($this->user->user_name ?? 'some one');
                    foreach ($data as $language => $datum) {
                        if (!empty($datum['device'])) {
                            Log::info('message: Mpush  start');

                            $job = new PostFans('publish_post', $userNickName, $language, (object)$datum['device'], $post_uuid);
                            $this->dispatchNow($job->onQueue(Constant::QUEUE_PUSH_NAME));
                            
                            //Mpush::dispatch('publish_post', $userNickName, $language, (object)$datum['device'], $post_uuid)->onQueue(Constant::QUEUE_PUSH_NAME);
                            Log::info('message: Mpush  end');
                        }
                    }
                }
            });

        Log::info('message::批量推送给粉丝  handle end ');

    }


}
