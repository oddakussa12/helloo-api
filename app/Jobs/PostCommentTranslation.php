<?php

namespace App\Jobs;

use App\Models\PostComment;
use Illuminate\Bus\Queueable;
use App\Services\TranslateService;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
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
        $translate = app(TranslateService::class);
        $lang = config('translatable.locales');
        $index = array_search($language , $lang);
        if($index!==false)
        {
            unset($lang[$index]);
        }
        foreach ($lang as $l)
        {
            if($l=='zh-HK')
            {
                $t = 'zh-TW';
            }else{
                $t = $l;
            }
            $content = $translate->translate($commentContent , array('target'=>$t));
            $postComment->fill([
                "{$l}"  => ['comment_content' => $content],
            ]);
            $postComment->save();
        }
    }
}
