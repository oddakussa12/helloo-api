<?php

namespace App\Jobs;

use App\Models\PostComment;
use Illuminate\Bus\Queueable;
use App\Services\NiuTranslateService;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Services\TencentTranslateService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class PostCommentTranslationV2 implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $languages;

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
        $this->languages = config('translatable.locales');
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
        $language = niuAzureToGoogle($this->contentLang=='und'?'en':$this->contentLang);
        $postComment = $this->postComment;
        $commentContent = $this->commentContent;
        $translate = app(NiuTranslateService::class);
        $languages = $this->languages;
        $index = array_search($language , $languages);
        if($index!==false)
        {
            unset($languages[$index]);
        }
        $postCommentData = array();
        foreach ($languages as $l)
        {
            if($l=='zh-HK')
            {
                continue;
            }else{
                $t = $l;
            }
            if($this->contentLang=='und')
            {
                $content = $commentContent;
            }else{
//                if(($language=='zh-CN'&&$t=='en')||($language=='en'&&$t=='zh-CN'))
//                {
//                    $service = new TencentTranslateService();
//                    $content = $service->translate($commentContent , array('source'=>$language , 'target'=>$t));
//                    if($content===false)
//                    {
//                        $content = $translate->translate($commentContent , array('source'=>$language , 'target'=>$t));
//                    }
//                }else{
//                    $content = $translate->translate($commentContent , array('source'=>$language , 'target'=>$t));
//                }
                $content = $translate->translate($commentContent , array('source'=>$language , 'target'=>$t));
            }
            $postCommentData[$l] = ['comment_content'=>$content];
        }
        $postComment->fill($postCommentData);
        $postComment->save();
    }
}
