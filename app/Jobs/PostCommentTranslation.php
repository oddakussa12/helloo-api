<?php

namespace App\Jobs;

use App\Models\PostComment;
use Illuminate\Bus\Queueable;
use App\Services\TranslateService;
use App\Services\V3TranslateService;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Services\TencentTranslateService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class PostCommentTranslation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    private $translate;

    protected $postComment;

    protected $contentLang;

    protected $commentContent;

    /**
     * Create a new job instance.
     *
     * @param PostComment $postComment
     * @param $contentLang
     * @param $commentContent
     */
    public function __construct(PostComment $postComment , $contentLang , $commentContent)
    {
        $this->postComment = $postComment;
        $this->contentLang = $contentLang;
        $this->commentContent= $commentContent;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $language = $this->contentLang=='und'?'en':$this->contentLang;
        $postComment = $this->postComment;
        $commentContent = $this->commentContent;
        if(config('common.google_translation_version')=='v2')
        {
            $translate = app(TranslateService::class);
        }else{
            $translate = app(V3TranslateService::class);
        }
        $languages = config('translatable.locales');
        $index = array_search($language , $languages);
        if($index!==false)
        {
            unset($languages[$index]);
        }
        foreach ($languages as $l)
        {
            if($l=='zh-HK')
            {
                $t = 'zh-TW';
            }else{
                $t = $l;
            }
            if($this->contentLang=='und')
            {
                $content = $commentContent;
            }else{
                if(($language=='zh-CN'&&$t=='en')||($language=='en'&&$t=='zh-CN'))
                {
                    $service = new TencentTranslateService();
                    $content = $service->translate($commentContent , array('source'=>$language , 'target'=>$t));
                    if($content===false)
                    {
                        $content = $translate->translate($commentContent , array('target'=>$t));
                    }
                }else{
                    $content = $translate->translate($commentContent , array('target'=>$t));
                }
            }
            $postComment->fill([
                "{$l}"  => ['comment_content' => $content],
            ]);
            $postComment->save();
        }
    }
}
