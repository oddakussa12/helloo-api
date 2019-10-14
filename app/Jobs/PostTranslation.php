<?php

namespace App\Jobs;

use App\Models\Post;
use Illuminate\Bus\Queueable;
use App\Services\TranslateService;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class PostTranslation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    private $translate;

    protected $post;
    private $post_title;
    private $post_content;
    private $postContentLang;
    private $postTitleLang;
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
     * @param $post_title
     * @param $post_content
     */
    public function __construct(Post $post , $postTitleLang , $postContentLang , $post_title , $post_content)
    {
        $this->post = $post;
        $this->postTitleLang = $postTitleLang;
        $this->postContentLang = $postContentLang;
        $this->post_title = $post_title;
        $this->post_content = $post_content;


        if(auth()->user()->user_last_name!='test!@#qaz')
        {
            notify('admin.post_notice' ,
                array(
                    'to'=>2 ,
                    'extra'=>array(
                        'post_id'=>$this->post->post_id,
                        'from_id'=>auth()->id() ,
                        'from_name'=>auth()->user()->user_name ,
                        'to_id'=>$this->post->owner->user_id ,
                        'to_name'=>$this->post->owner->user_name ,
                    ) ,
                    'url'=>'/notification/post/'.$this->post->post_id,
                ),
                true
            );
        }


    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        dynamicSetLocales(array($this->postTitleLang , $this->postContentLang));
        $post = $this->post;
        $postTitle = $this->post_title;
        $postContent = $this->post_content;
        $translate = app(TranslateService::class);
        $lang = config('translatable.locales');
        foreach ($lang as $l)
        {
            if($l=='zh-HK')
            {
                $t = 'zh-TW';
            }else{
                $t = $l;
            }
            if(empty($postTitle)||$l==$this->postTitleLang)
            {
                $title = $postTitle;
            }else{
                $title = $translate->translate($postTitle , array('target'=>$t));
            }
            if(empty($postContent)||$l==$this->postContentLang)
            {
                $content = $postContent;
            }else{
                $content = $translate->translate($postContent , array('target'=>$t , 'format'=>'html'));
            }
            $post->fill([
                "{$l}"  => ['post_title' => $title , 'post_content'=>$content],
            ]);
            $post->save();
        }


    }
}
