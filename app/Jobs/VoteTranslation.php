<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\VoteDetail;
use App\Models\VoteDetailTranslation;
use App\Services\CustomizeTranslateService;
use App\Traits\CachableUser;
use App\Traits\CachablePost;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Services\TencentTranslateService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class VoteTranslation implements ShouldQueue
{
    use CachablePost,CachableUser,Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $languages;

    private $translate;

    protected $user;
    protected $post;
    private $post_title;
    private $post_content;
    private $contentLang;
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
     * @param User $user
     * @param VoteDetail $voteDetail
     * @param $content
     * @param $lang
     */
    public function __construct(User $user, VoteDetail $voteDetail, $content, $lang)
    {
        $this->languages    = config('translatable.locales');
        $this->user         = $user;
        $this->post         = $voteDetail;
        $this->contentLang  = $lang;
        $this->post_content = $content;
    }
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $post        = $this->post;
        $postContent = $this->post_content;
        $contentLang = $this->contentLang;
        $languages   = $this->languages;

        /*if(config('common.google_translation_version')=='v2')
        {
            $translate = app(TranslateService::class);
        }else{
            $translate = app(V3TranslateService::class);
        }*/

        // $voteJob   = (new CustomizeTranslateService($languages))->translate($postContent, ['scource'=>$contentLang]);
        // $languages = array_diff($languages , [$contentLang]);

        $translate           = app(CustomizeTranslateService::class)->setLanguages($languages);
        $contentTranslations = $translate->translate($postContent , ['source'=>$contentLang]);
        $translations        = $contentTranslations->getTranslations();
        $translations        = array_merge($translations, [$contentLang=>$postContent]);
        foreach ($translations as $l=>$translation) {
            VoteDetailTranslation::updateOrCreate(
                ['vote_detail_id'=>$post->id, 'post_id'=>$post->post_id, 'locale'=>$l],
                ['content'=>$translation]);
        }
    }
}
