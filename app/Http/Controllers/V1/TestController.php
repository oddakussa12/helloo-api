<?php

namespace App\Http\Controllers\V1;

use App\Custom\RedisList;
use Google\ApiCore\ApiException;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;
use Google\Cloud\Storage\StorageClient;
use Google\Cloud\Translate\V3\GcsSource;
use Google\Cloud\Translate\V3\Glossary;
use Google\Cloud\Translate\V3\GlossaryInputConfig;
use Google\Cloud\Translate\V3\Glossary\LanguageCodesSet;
use App\Repositories\Contracts\PostRepository;
use App\Repositories\Contracts\UserRepository;
use Illuminate\Database\Concerns\BuildsQueries;
use Google\Cloud\Translate\V3\TranslationServiceClient;
use Google\Cloud\Translate\V3\TranslateTextGlossaryConfig;


class TestController extends BaseController
{
    //
    use BuildsQueries;
    public function index(Request $request)
    {
        app(PostRepository::class)->customFinePost();
//        var_dump(app(UserRepository::class)->hiddenPosts());
//        app(UserRepository::class)->updateHiddenPosts(auth()->id() , 'adfaljj-djlkaj');
//        $perPage = 2;
//
//        $redis = new RedisList();
//        $pageName = 'page';
//        $page = $request->input('page' , 1);
//        $index = $request->input('index' , 1);
//        $key = 'post_index_'.$index;
//
////        for($i=1;$i<=100;$i++)
////        {
////            $rand = mt_rand(1 , 10000);
////            $redis->zAdd('tester' , $rand, 'c'.$rand);
////        }
//        $offset = ($page-1)*$perPage;
//        $list = $redis->zRangByScore($key , '-inf' , '+inf' , true , array($offset , $perPage));
//        $total = $redis->zSize($key);
//        return $this->paginator($list, $total, $perPage, $page, [
//            'path' => Paginator::resolveCurrentPath(),
//            'pageName' => $pageName,
//        ]);
    }

    public function clearCache(Request $request)
    {
        Cache::forget('fine_post');
        app(PostRepository::class)->getFinePostIds();
        return $this->response->noContent();
    }

    public function test()
    {

        putenv('GOOGLE_APPLICATION_CREDENTIALS='.config('common.google_application_credentials'));
        $text = "﻿cool,what?<br />fuck you!";
        $sourceLanguage='en';
        $targetLanguage='zh-CN';
        $location = 'us-central1';
        $projectId = 'speachregins';

        $glossaryId = 'yooul_v3_glossary_20200325';
        $bucketName = 'translation_v3_glossary_2020324';
        $inputUri = 'gs://'.$bucketName.'/glossary.csv';
        $translationServiceClient = new TranslationServiceClient();
        $glossaryPath = $translationServiceClient->glossaryName(
            $projectId,
            $location,
            $glossaryId
        );
        $contents = [$text];
        $formattedParent = $translationServiceClient->locationName(
            $projectId,
            $location
        );
        $glossaryConfig = new TranslateTextGlossaryConfig();
        $glossaryConfig->setGlossary($glossaryPath);

// Optional. Can be "text/plain" or "text/html".
        $mimeType = 'text/html';

        try {
            $response = $translationServiceClient->translateText(
                $contents,
                $targetLanguage,
                $formattedParent,
                [
                    'sourceLanguageCode' => $sourceLanguage,
                    'glossaryConfig' => $glossaryConfig,
                    'mimeType' => $mimeType
                ]
            );
            // Display the translation for each input text provided
            foreach ($response->getGlossaryTranslations() as $translation) {
                printf('Translated text: %s' . PHP_EOL, $translation->getTranslatedText());
            }
        } finally {
            $translationServiceClient->close();
        }

    }
}
