<?php

namespace App\Jobs;

use App\Models\Es;
use App\Models\User;
use App\Models\Post;
use App\Traits\CachableUser;
use App\Traits\CachablePost;
use Illuminate\Bus\Queueable;
use App\Services\NiuTranslateService;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Services\TencentTranslateService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\PostTranslation as PostTranslationModel;

class PostTranslationV2 implements ShouldQueue
{
    use CachablePost,CachableUser,Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
                        $title = $translate->translate($postTitle , array('source'=>$this->postTitleLang , 'target'=>$t));
                    }
                }else{
                    $title = $translate->translate($postTitle , array('source'=>$this->postTitleLang , 'target'=>$t));
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
                        $content = $translate->translate($postContent , array('source'=>$this->postContentLang , 'target'=>$t));
                    }
                }else {
                    $content = $translate->translate($postContent, array('source'=>$this->postContentLang , 'target' => $t));
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

        $tt = 1;
        // 组装数据 插入ES

        $post->post_uuid  = $post->post_uuid->toString();
        $post->create_at  = optional($post->post_created_at)->toDateTimeString();
        $post->post_media = (!empty($post->post_type) && !empty($post->post_media)) ? postMedia($post->post_type, $post->post_media) : null;

        $result           = $post->getAttributes();
        $postInfo         = $post->getTranslationsArray();

        unset($result['post_country_id'], $result['post_rate'], $result['post_event_country_id'], $result['post_created_at']);

        $postList = array_map(function($v) use ($result){unset($v['post_title']);return array_merge($result, $v);}, $postInfo);
        $postList = array_column($postList, null);

        $c = config('scout.elasticsearch.post');
        $data     = (new Es(config('scout.elasticsearch.post')))->batchCreate($postList);
        if ($data==null) {
          $data =  (new Es(config('scout.elasticsearch.post')))->batchCreate($postList);
        }
    }
}
