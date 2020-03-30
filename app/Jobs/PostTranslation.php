<?php

namespace App\Jobs;

use App\Models\Post;
use App\Custom\RedisList;
use Illuminate\Bus\Queueable;
use App\Services\TranslateService;
use App\Services\V3TranslateService;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Services\TencentTranslateService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\PostTranslation as PostTranslationModel;

class PostTranslation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $languages;

    private $translate;

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
     * @param Post $post
     * @param $postTitleLang
     * @param $postContentLang
     * @param $postTitleDefaultLang
     * @param $postContentDefaultLang
     * @param $post_title
     * @param $post_content
     */
    public function __construct(Post $post , $postTitleLang , $postContentLang , $postTitleDefaultLang , $postContentDefaultLang , $post_title , $post_content)
    {
        $this->languages = config('translatable.locales');
        $this->post = $post;
        $this->postTitleLang = $postTitleLang;
        $this->postContentLang = $postContentLang;
        $this->post_title = $post_title;
        $this->post_content = $post_content;
        $this->postTitleDefaultLang = $postTitleDefaultLang;
        $this->postContentDefaultLang = $postContentDefaultLang;

        if (auth()->check()) {
            $user = auth()->user();
            $user->increment('user_score' , 2);
        }
        $redis = new RedisList();
        $postKey = 'post_index_new';
        $redis->zAdd($postKey , strtotime(optional($this->post->post_created_at)->toDateTimeString()) , $this->post->getKey());
        $redis->remZsetList($postKey , 1000);

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
        if(config('common.google_translation_version')=='v2')
        {
            $translate = app(TranslateService::class);
        }else{
            $translate = app(V3TranslateService::class);
        }
        $languages = $this->languages;
        foreach ($languages as $l)
        {
            if($l=='zh-HK')
            {
                continue;
            }else{
                $t = $l;
            }
            if(empty($postTitle)||$l==$this->postTitleLang||$this->postTitleDefaultLang=='und')
            {
                $title = $postTitle;
            }else{
                if((($this->postTitleLang=='zh-CN'&&$t=='en')||($this->postTitleLang=='en'&&$t=='zh-CN'))&&strlen(trim($postTitle))<=1024)
                {
                    $service = new TencentTranslateService();
                    $title = $service->translate($postTitle , array('source'=>$this->postTitleLang , 'target'=>$t));
                    if($title===false)
                    {
                        $title = $translate->translate($postTitle , array('target'=>$t , 'resource'=>$this->postTitleLang));
                    }
                }else{
                    $title = $translate->translate($postTitle , array('target'=>$t , 'resource'=>$this->postTitleLang));
                }
            }

            if(empty($postContent)||$l==$this->postContentLang||$this->postContentDefaultLang=='und')
            {
                $content = $postContent;
            }else{
                if((($this->postContentLang=='zh-CN'&&$t=='en')||($this->postContentLang=='en'&&$t=='zh-CN'))&&strlen(trim($postContent))<=1024)
                {
                    $service = new TencentTranslateService();
                    $content = $service->translate($postContent , array('source'=>$this->postContentLang , 'target'=>$t));
                    if($content===false)
                    {
                        $content = $translate->translate($postContent , array('target'=>$t , 'resource'=>$this->postContentLang));
                    }
                }else {
                    $content = $translate->translate($postContent, array('target' => $t , 'resource'=>$this->postContentLang));
                }
            }
            $post->fill([
                "{$l}"  => ['post_title' => $title , 'post_content'=>$content],
            ]);
            $post->save();
        }
        $post->fill([
            "zh-HK"  => ['post_title' => $post->translate('zh-TW')->post_title , 'post_content'=>$post->translate('zh-TW')->post_content],
        ]);
        $post->save();
        $exceptLanguages = array_unique(array_diff($exceptLanguages , $languages));
        foreach ($exceptLanguages as $l)
        {
            if(empty($postTitle)||$l==$this->postTitleLang||$this->postTitleDefaultLang=='und')
            {
                $title = $postTitle;
            }else{
                $title = $translate->translate($postTitle , array('target'=>$l , 'resource'=>$this->postTitleLang));
            }
            if(empty($postContent)||$l==$this->postContentLang||$this->postContentDefaultLang=='und')
            {
                $content = $postContent;
            }else{
                $content = $translate->translate($postContent, array('target' => $l , 'resource'=>$this->postContentLang));
            }
            PostTranslationModel::updateOrCreate(
                ['post_id' => $post->getKey(), 'post_locale' => $l] ,
                ['post_title'=>$title , 'post_content'=>$content]
            );
        }
    }
}
