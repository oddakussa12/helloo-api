<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Post;
use App\Traits\CachableUser;
use App\Traits\CachablePost;
use Illuminate\Bus\Queueable;
use App\Custom\Constant\Constant;
use App\Services\NiuTranslateService;
use App\Services\CustomizeTranslateService;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Services\TencentTranslateService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Bus\DispatchesJobs;
use App\Models\PostTranslation as PostTranslationModel;

class PostTranslationV3 implements ShouldQueue
{
    use CachablePost,CachableUser,DispatchesJobs, InteractsWithQueue, Queueable, SerializesModels;

    private $languages;

    private $translate;

    protected $user;
    protected $post;
    private $post_title;
    private $post_content;
    private $postContentLang;
    private $postTitleLang;
    private $postTitleDefaultLang;
    private $postContentDefaultLang;
    /**
     * @var \Illuminate\Config\Repository
     */
    private $locales;

    /**
     * Create a new job instance.
     *
     * @param $user
     * @param $post
     * @param $postTitleLang
     * @param $postContentLang
     * @param $postTitleDefaultLang
     * @param $postContentDefaultLang
     * @param $post_title
     * @param $post_content
     */
    public function __construct(User $user , Post $post , $postTitleLang , $postContentLang , $postTitleDefaultLang , $postContentDefaultLang , $post_title , $post_content)
    {
        $this->languages = config('translatable.locales');
        $this->user = $user;
        $this->post = $post;
        $this->postTitleLang = $postTitleLang;
        $this->postContentLang = $postContentLang;
        $this->post_title = $post_title;
        $this->post_content = $post_content;
        $this->postTitleDefaultLang = $postTitleDefaultLang;
        $this->postContentDefaultLang = $postContentDefaultLang;

//        $user->increment('user_score' , 2);
        $this->updateUserPostCount($user->user_id);
//        $this->updateUserScoreRank($user->user_id , 2);
        $this->initPost($post);
//        $this->handle();
    }
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $postData = array();
        $languages = $this->languages;
        $post = $this->post;
        $postTitle = $this->post_title;
        $postContent = $this->post_content;
        $exceptLanguages = array($this->postTitleLang , $this->postContentLang);
        $languages = array_diff($languages , $exceptLanguages);
        $translate = app(CustomizeTranslateService::class)->setLanguages($languages);
        $contentTranslations = $translate->translate($postContent , array('source'=>$this->postContentLang));
        foreach ($contentTranslations as $l=>$translation)
        {
            $postData[$l] = ['post_title' => $postTitle , 'post_content'=>$translation];
        }
        if(empty($postTitle))
        {
            foreach ($languages as $l)
            {
                $postData[$l]['post_title'] = '';
            }
        }else{
            $titleTranslations = $translate->translate($postTitle , array('source'=>$this->postTitleLang));
            foreach ($titleTranslations as $l=>$translation)
            {
                $postData[$l]['post_title'] = $translation;
            }
        }
        $post->fill($postData);
        $post->save();
        // 组装数据 插入ES
        PostEs::dispatch($post)->onQueue(Constant::QUEUE_ES_POST);

        // 批量推送给粉丝
        PostFans::dispatch($this->user, $post, $postData)->onQueue(Constant::QUEUE_PUSH_POST);
    }

}
