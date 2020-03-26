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
        echo PHP_EOL.'getListGlossaries'.PHP_EOL;
        $this->getListGlossaries($projectId,$location);

    }

    public function getListGlossaries($projectId,$location)
    {
        $translationServiceClient = new TranslationServiceClient();

        /** Uncomment and populate these variables in your code */
        $formattedParent = $translationServiceClient->locationName(
            $projectId,
            $location
        );

        try {
            // Iterate through all elements
            $pagedResponse = $translationServiceClient->listGlossaries($formattedParent);
            foreach ($pagedResponse->iterateAllElements() as $responseItem) {
                printf('Glossary name: %s' . PHP_EOL, $responseItem->getName());
                printf('Entry count: %s' . PHP_EOL, $responseItem->getEntryCount());
                printf(
                    'Input URI: %s' . PHP_EOL,
                    $responseItem->getInputConfig()
                        ->getGcsSource()
                        ->getInputUri()
                );
            }
        } finally {
            $translationServiceClient->close();
        }
    }


}
