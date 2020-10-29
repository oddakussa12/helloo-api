<?php

namespace App\Http\Controllers\V1;

use App\Custom\Constant\Constant;
use App\Jobs\PostEs;
use App\Jobs\VoteTranslation;
use App\Models\Post;
use App\Models\VoteDetail;
use App\Models\VoteLog;
use App\Resources\PostVoteCollection;
use App\Services\CustomizeNiuTranslateService;
use App\Services\CustomizeTranslateService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;
use App\Models\Banner;
use App\Custom\RedisList;
use Illuminate\Http\Request;
use App\Events\PostViewEvent;
use App\Jobs\PostTranslation;
use App\Jobs\PostTranslationV2;
use App\Resources\PostCollection;
use App\Services\TranslateService;
use App\Resources\BannerCollection;
use Illuminate\Support\Facades\Redis;
use App\Http\Requests\StorePostRequest;
use App\Services\AzureTranslateService;
use App\Resources\PostPaginateCollection;
use App\Repositories\Contracts\PostRepository;

class PostVoteController extends BaseController
{
    /**
     * @var PostRepository
     */
    private $post;
    /**
     * @var TranslateService||V3TranslateService
     */
    private $translate;

    /**
     * Display a listing of the resource.
     *
     * @param PostRepository $post
     * @param AzureTranslateService $azureTranslateService
     * @param TranslateService $translateService
     */

    public function __construct(PostRepository $post,AzureTranslateService $azureTranslateService , TranslateService $translateService)
    {
        $this->post = $post;
//        if(config('common.translation_version')==='niu')
//        {
//            $this->translate = $azureTranslateService;
//        }else{
//            $this->translate = $translateService;
//        }
        $this->translate = $translateService;
    }

    /**
     * @param Request $request
     * @return \Dingo\Api\Http\Response|void
     * 投票
     */
    public function vote(Request $request)
    {
        $user    = auth()->user();
        $user_id = $user->user_id;
        $country = $user->user_country_id;

        if (empty($user_id)) {
            return $this->response->errorForbidden();
        }
        $vote_id   = $request->input('vote_id');
        $post_uuid = $request->input('post_uuid');
        $post      = Post::where('post_uuid', $post_uuid)->first();

        if (empty($post)) {
            return $this->response->errorBadRequest();
        }
        $voteInfo  = VoteDetail::where(['post_id'=>$post['post_id'], 'id'=>$vote_id])->first();

        if (empty($voteInfo)) {
            return $this->response->errorBadRequest();
        }

        $memKey = config('redis-key.post.post_vote_data').$post['post_id'];
        $where  = ['user_id'=>$user_id, 'post_id'=>$post['post_id'], 'vote_id'=>$vote_id];
        $flag   = true;

        $voteAll= Redis::hgetall($memKey);
        if (!empty($voteAll)) {
            foreach ($voteAll as $key=>$item) {
                $item = json_decode($item, true);
                if (in_array($user_id, $item['users'])) {
                    $flag = false;
                    break;
                }
            }
        }

        if ($flag==false) {
            Log::info('message：已投过票', $where);
            return $this->response->accepted();
        }

        $vote = !empty($voteAll[$vote_id]) ? $voteAll[$vote_id] : ['country'=>[], 'users'=>[]];
        $vote['country'][$country] = array_key_exists($country, $vote['country']) ? $vote['country'][$country]+1 : 1;
        $vote['users'] = array_merge($vote['users'], [$user_id]);

        Redis::hset($memKey, $vote_id, collect($vote));
        VoteDetail::where(['post_id'=>$post['post_id'], 'id'=>$vote_id])->update(['country'=>serialize($vote['country']), 'vote_num'=>$voteInfo['vote_num']+1]);
        VoteLog::create($where);

        return $this->response->accepted();

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StorePostRequest $request
     * @return PostCollection
     * @throws \Exception
     */
    public function store(StorePostRequest $request)
    {
        $post_title         = clean($request->input('post_title' , ''));
        $post_content       = clean($request->input('post_content' , ''));
        $post_type          = $request->input('post_type');
        $post_event_country = $request->input('post_event_country');
        $radio              = $request->input('radio');
        $longitude          = $request->input('longitude'); // 经度
        $latitude           = $request->input('latitude'); // 纬度
        $showType           = $request->input('show_type', 1); // 可见范围
	    $post_image         = $request->input('post_image' , []);
        $topics             = array_diff((array)$request->input('topics', []), [null , '']);

        \Validator::make(array('post_content'=>$post_content), [
            'post_content' => ['bail','required','string','between:1,3000'],
        ])->validate();

        // 不是投票贴
        if ($post_type !== 'vote') {
            return $this->response->errorBadRequest();
        }

        $prohibited_content = config('common.prohibited_content');
        if(!blank($prohibited_content) && str_contains($post_content , $prohibited_content))
        {
            $uuid = config('common.prohibited_default_uuid');
            if(blank($uuid)) {
                return $this->response->created();
            }
            $post = $this->post->showByUuid($uuid);
            return new PostCollection($post);
        }
        try {
            $postTitleLang             = empty($post_title)    ? 'en' : $this->translate->detectLanguage($post_title);
            $post_title_default_locale = $postTitleLang=='und' ? 'en' : $postTitleLang;
            if(empty($post_content))
            {
                $postContentLang = 'und';
                $post_content_default_locale = 'en';
            } else {
                $postContentLang = $this->translate->detectLanguage($post_content);
                $post_content_default_locale = $postContentLang=='und'?'en':$postContentLang;
            }
        } catch (\Exception $e) {
            \Log::error(\json_encode($e->getMessage() , JSON_UNESCAPED_UNICODE));
            abort(424 , 'Sorry guys! We are updating our services in the next 24 hours. We apologize for the inconvenience !');
        }
        $poster        = auth()->user();
        $titleLocale   = niuAzureToGoogle($post_title_default_locale);
        $contentLocale = niuAzureToGoogle($post_content_default_locale);
        $post_info     = [
            'user_id'          => $poster->user_id,
            'post_uuid'        => Uuid::uuid1(),
            'post_category_id' => $post_image ? 2 : 1,
            'post_country_id'  => $poster->user_country_id,
            'post_type'        => $post_type,
            'longitude'        => $longitude,
            'latitude'         => $latitude,
            'showType'         => $showType,
            'post_rate'        => first_rate_comment_v2(),
            'post_default_locale'         => $titleLocale,
            'post_event_country_id'       => !empty($post_event_country) ? $post_event_country : '-1',
            'post_content_default_locale' => $contentLocale,
        ];

        dynamicSetLocales(array($titleLocale , $contentLocale));

        if($titleLocale!=$contentLocale)
        {
            !empty($post_title) && $post_info[$titleLocale] = array('post_title'=>$post_title,'post_content'=>'');
            $post_info[$contentLocale] = array('post_title'=>'','post_content'=>$post_content);
        } else {
            $post_info[$titleLocale] = array('post_title'=>$post_title,'post_content'=>$post_content);
        }

        $post = $this->post->store($post_info);

        if(!empty($topics)) {
            $post = $this->post->attachTopics($post , $topics);
        }

        // 投票贴
        $radio = !is_array($radio) ? json_decode($radio, true) : $radio;
        foreach ($radio as $key=>$item) {
            $voteInfo['post_id']        = $post->post_id;
            $voteInfo['user_id']        = $poster->user_id;
            $voteInfo['tab_name']       = $item['tab_name'] ?? '';
            $voteInfo['default_locale'] = !empty($item['text']) ? $this->translate->detectLanguage($item['text']) : 'en';
            $voteInfo['vote_type']      = !empty($item['image']) ? 'image' : 'text';
            if (!empty($item['image'])) {
                $voteInfo['vote_media'] = [
                    'image'      => [
                    'image_from' => 'upload',
                    'image_cover'=> $item['image'][0],
                    'image_url'  => $item['image'],
                    'image_count'=> 1
                ]];
            }
            $voteDetail = VoteDetail::create($voteInfo);

            // 当选项内容为 文本时，需要进行翻译
            if (!empty($item['text'])) {
                $textLang  = $this->translate->detectLanguage($item['text']) ?? 'en';

                if (config('common.translation_version')==='niu') {
                    $voteJob = (new VoteTranslation($poster, $voteDetail, $item['text'], $textLang));
                } else {
                    $voteJob = (new VoteTranslation($poster, $voteDetail, $item['text'], $textLang));
                }
                $this->dispatchNow($voteJob);
                // $this->dispatch($voteJob->onQueue(Constant::QUEUE_CUSTOM_POST_TRANSLATION));

                // $languages = array_diff(config('translatable.locales') , [$textLang]);
                /*$translate = app(CustomizeTranslateService::class)->setLanguages($languages);
                $contentTranslations = $translate->translate($item['text'] , array('source'=>$textLang));*/
            }
        }

        if (config('common.translation_version')==='niu')
        {
            $job = new PostTranslationV2($poster , $post , $titleLocale , $contentLocale , $postTitleLang , $postContentLang , $post_title , $post_content);
        } else {
            $job = new PostTranslation($poster , $post , $titleLocale , $contentLocale , $postTitleLang , $postContentLang , $post_title , $post_content);
        }
//        $this->dispatchNow($job);
        $this->dispatch($job->onQueue(Constant::QUEUE_POST_TRANSLATION));

        return new PostCollection($post);

    }





}
