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
use Illuminate\Support\Facades\DB;

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
    private $voteRadio;
    private $voteLang;
    /**
     * @var array
     */
    private $voteDetail;

    /**
     * Create a new job instance.
     *
     * @param User $user
     * @param array $voteDetail VoteDetail
     * @param $voteContent
     * @param $voteLang
     */
    public function __construct(User $user, array $voteDetail, $voteContent, $voteLang)
    {
        $this->languages  = config('translatable.locales');
        $this->user       = $user;
        $this->voteDetail = $voteDetail;
        $this->voteLang   = $voteLang;
        $this->voteRadio  = $voteContent;
    }
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $voteDetail = $this->voteDetail;
        $voteRadio  = $this->voteRadio;
        $voteLang   = $this->voteLang;
        $languages  = $this->languages;


//        dump($voteRadio, $voteLang, $voteDetail, $languages);

        /*if(config('common.google_translation_version')=='v2')
        {
            $translate = app(TranslateService::class);
        }else{
            $translate = app(V3TranslateService::class);
        }*/

        foreach ($voteDetail as $k=>$detail) {
            foreach ($voteRadio as $key=>$item) {
                if ($detail->tab_name == $item['tab_name']) {
                    $postContent         = $item['text'];
                    $contentLang         = $voteLang[$key]['languageCode'];
                    $languages           = array_values(array_diff($languages, [$contentLang]));
                    $translate           = app(CustomizeTranslateService::class)->setLanguages($languages);
                    $contentTranslations = $translate->translate($postContent , ['source'=>$contentLang]);
                    $translations        = $contentTranslations->getTranslations();
                    $translations        = array_merge($translations, [$contentLang=>$postContent]);
                    $data = [];
                    foreach ($translations as $l=>$translation) {
                           $data[] = [
                               'vote_detail_id' => $detail->id,
                               'post_id'       => $detail->post_id,
                               'locale'        => $l,
                               'content'       => $translation,
                               'created_at'    => date('Y-m-d H:i:s'),
                               'updated_at'    => date('Y-m-d H:i:s'),
                           ];
                    }
                    $data && DB::table('vote_details_translations')->insert($data);
                }

            }
        }


        // $languages = array_diff(config('translatable.locales') , [$textLang]);
        /*$translate = app(CustomizeTranslateService::class)->setLanguages($languages);
        $contentTranslations = $translate->translate($item['text'] , array('source'=>$textLang));*/
    }
}
