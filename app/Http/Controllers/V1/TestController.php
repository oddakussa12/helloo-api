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
use Google\Cloud\Translate\V2\TranslateClient;
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
        return $this->response->noContent();
    }

    public function test()
    {
        return $this->response->created();
        putenv('GOOGLE_APPLICATION_CREDENTIALS='.config('common.google_application_credentials'));
        $text = "﻿cool,what?<br />fuck you!,Yooul,what are you dong? cool";
        $sourceLanguage='en';
        $targetLanguage='zh-CN';
        $location = 'us-central1';
        $projectId = 'speachregins';
        $glossaryId = 'yooul_v3_glossary_20200325';
        $bucketName = 'translation_v3_glossary_2020324';
        $inputUri = 'gs://'.$bucketName.'/glossary.csv';
//        echo PHP_EOL.'getListGlossaries'.PHP_EOL;
//        $this->getListGlossaries($projectId,$location);
//        echo PHP_EOL.'getGlossaryInfo'.PHP_EOL;
//        $this->getGlossaryInfo($projectId, $location , $glossaryId);
//        echo PHP_EOL.'v3'.PHP_EOL;
//        $this->v2($text , $targetLanguage);
//        echo PHP_EOL.'v3'.PHP_EOL;
//        $this->v3($projectId, $location , $glossaryId, $targetLanguage , $sourceLanguage , $text);
        echo PHP_EOL.'createGlossary'.PHP_EOL;
        $this->createGlossary($projectId,$location,$glossaryId,$inputUri);

    }

    public function v2($text,$targetLanguage)
    {
        $translate = new TranslateClient();
        $result = $translate->translate($text, [
            'target' => $targetLanguage,
        ]);
        print("Source language: $result[source]\n");
        print("Translation: $result[text]\n");
    }


    public function v3($projectId, $location , $glossaryId, $targetLanguage , $sourceLanguage , $text)
    {
        $translationServiceClient = new TranslationServiceClient();

        $glossaryPath = $translationServiceClient->glossaryName(
            $projectId,
            'us-central1',
            $glossaryId
        );
        $contents = [$text];
        $formattedParent = $translationServiceClient->locationName(
            $projectId,
            'us-central1'
        );
        $glossaryConfig = new TranslateTextGlossaryConfig();
        $glossaryConfig->setGlossary($glossaryPath);

// Optional. Can be "text/plain" or "text/html".
        $mimeType = 'text/plain';

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

    public function getGlossaryInfo($projectId, $location , $glossaryId)
    {
        $translationServiceClient = new TranslationServiceClient();

        $formattedName = $translationServiceClient->glossaryName(
            $projectId,
            $location,
            $glossaryId
        );

        try {
            $response = $translationServiceClient->getGlossary($formattedName);
            printf('Glossary name: %s' . PHP_EOL, $response->getName());
            printf('Entry count: %s' . PHP_EOL, $response->getEntryCount());
            printf(
                'Input URI: %s' . PHP_EOL,
                $response->getInputConfig()
                    ->getGcsSource()
                    ->getInputUri()
            );
        } finally {
            $translationServiceClient->close();
        }
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

    public function createGlossary($projectId,$location,$glossaryId,$inputUri)
    {
        $glossaryId .=$glossaryId.mt_rand(1,10);
        $translationServiceClient = new TranslationServiceClient(array('serviceName'=>'google.longrunning.Operations/GetOperation'));

        /** Uncomment and populate these variables in your code */
// $projectId = '[Google Cloud Project ID]';
// $glossaryId = 'my_glossary_id_123';
// $inputUri = 'gs://cloud-samples-data/translation/glossary.csv';
        $formattedParent = $translationServiceClient->locationName(
            $projectId,
            $location
        );
        $formattedName = $translationServiceClient->glossaryName(
            $projectId,
            $location,
            $glossaryId
        );
        $languageCodesElement = 'en';
        $languageCodesElement2 = 'zh-CN';
        $languageCodes = [$languageCodesElement, $languageCodesElement2];
        $languageCodesSet = new LanguageCodesSet();
        $languageCodesSet->setLanguageCodes($languageCodes);
        $gcsSource = (new GcsSource())
            ->setInputUri($inputUri);
        $inputConfig = (new GlossaryInputConfig())
            ->setGcsSource($gcsSource);
        $glossary = (new Glossary())
            ->setName($formattedName)
            ->setLanguageCodesSet($languageCodesSet)
            ->setInputConfig($inputConfig);

        try {
            $operationResponse = $translationServiceClient->createGlossary(
                $formattedParent,
                $glossary
            );
            $operationResponse->pollUntilComplete();die;
            if ($operationResponse->operationSucceeded()) {
                $response = $operationResponse->getResult();
                printf('Created Glossary.' . PHP_EOL);
                printf('Glossary name: %s' . PHP_EOL, $response->getName());
                printf('Entry count: %s' . PHP_EOL, $response->getEntryCount());
                printf(
                    'Input URI: %s' . PHP_EOL,
                    $response->getInputConfig()
                        ->getGcsSource()
                        ->getInputUri()
                );
            } else {
                $error = $operationResponse->getError();
                var_dump($error);
                // handleError($error)
            }
        } finally {
            $translationServiceClient->close();
        }
    }


}
