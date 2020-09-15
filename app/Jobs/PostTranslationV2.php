<?php

namespace App\Jobs;

use App\Custom\Constant\Constant;
use App\Models\User;
use App\Models\Post;
use App\Traits\CachableUser;
use App\Traits\CachablePost;
use GPBMetadata\Google\Api\Log;
use Illuminate\Bus\Queueable;
use App\Services\NiuTranslateService;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Services\TencentTranslateService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Bus\DispatchesJobs;
use App\Models\PostTranslation as PostTranslationModel;

class PostTranslationV2 implements ShouldQueue
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

        $user->increment('user_score' , 2);
        $this->updateUserPostCount($user->user_id);
        $this->updateUserScoreRank($user->user_id , 2);
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
        $exceptLanguages = array($this->postTitleLang , $this->postContentLang);
        $post = $this->post;
        $postTitle = $this->post_title;
        $postContent = $this->post_content;
        $translate = app(NiuTranslateService::class);
        $languages = $this->languages;
        $postData = array();
        foreach ($languages as $l)
        {
            if($l=='zh-HK') {
                continue;
            }else{
                $t = $l;
            }
            if(empty($postTitle)||$l==$this->postTitleLang||$this->postTitleDefaultLang=='und')
            {
                $title = $postTitle;
            }else{
//                if((($this->postTitleLang=='zh-CN'&&$t=='en')||($this->postTitleLang=='en'&&$t=='zh-CN'))&&strlen(trim($postTitle))<=1024)
//                {
//                    $service = new TencentTranslateService();
//                    $title = $service->translate($postTitle , array('source'=>$this->postTitleLang , 'target'=>$t));
//                    if($title===false)
//                    {
//                        $title = $translate->translate($postTitle , array('source'=>$this->postTitleLang , 'target'=>$t));
//                    }
//                }else{
//                    $title = $translate->translate($postTitle , array('source'=>$this->postTitleLang , 'target'=>$t));
//                }
                $title = $translate->translate($postTitle , array('source'=>$this->postTitleLang , 'target'=>$t));
            }

            if(empty($postContent)||$l==$this->postContentLang||$this->postContentDefaultLang=='und')
            {
                $content = $postContent;
            }else{
//                if((($this->postContentLang=='zh-CN'&&$t=='en')||($this->postContentLang=='en'&&$t=='zh-CN'))&&strlen(trim($postContent))<=1024)
//                {
//                    $service = new TencentTranslateService();
//                    $content = $service->translate($postContent , array('source'=>$this->postContentLang , 'target'=>$t));
//                    if($content===false)
//                    {
//                        $content = $translate->translate($postContent , array('source'=>$this->postContentLang , 'target'=>$t));
//                    }
//                }else {
//                    $content = $translate->translate($postContent, array('source'=>$this->postContentLang , 'target' => $t));
//                }
                $content = $translate->translate($postContent, array('source'=>$this->postContentLang , 'target' => $t));
            }
            $postData[$l] = ['post_title' => $title , 'post_content'=>$content];
        }
        $post->fill($postData);
        $post->save();
        $exceptLanguages = array_unique(array_diff($exceptLanguages , $languages));
        foreach ($exceptLanguages as $l)
        {
            if(empty($postTitle)||$l==$this->postTitleLang||$this->postTitleDefaultLang=='und')
            {
                $title = $postTitle;
            }else{
                $title = $translate->translate($postTitle , array('source'=>$this->postTitleLang , 'target'=>$l));
            }
            if(empty($postContent)||$l==$this->postContentLang||$this->postContentDefaultLang=='und')
            {
                $content = $postContent;
            }else{
                $content = $translate->translate($postContent, array('source'=>$this->postContentLang , 'target' => $l));
            }
            PostTranslationModel::updateOrCreate(
                ['post_id' => $post->getKey(), 'post_locale' => $l] ,
                ['post_title'=>$title , 'post_content'=>$content]
            );
        }

        // 组装数据 插入ES
        PostEs::dispatch($post)->onQueue(Constant::QUEUE_ES_POST);

        // 批量推送给粉丝
        //PostFans::dispatch($this->user, $post, $postData)->onQueue(Constant::QUEUE_PUSH_POST);

        $job = new PostFans($this->user, $post, $postData);
        Log::info('message::批量推送给粉丝  start');
        $this->dispatchNow($job->onQueue(Constant::QUEUE_PUSH_POST));
        Log::info('message::批量推送给粉丝  end');

    }

}
