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

        $translationServiceClient = new TranslationServiceClient();
        /** Uncomment and populate these variables in your code */
        $location = 'us-central1';
         $projectId = 'speachregins';
         $glossaryId = 'translation_v3_glossary_2020324';
         $bucketName = 'translation_v3_glossary_2020324';
         $inputUri = 'gs://translation_glossary/glossary.csv';
//         $storage = new StorageClient();
//
////         $bucket = $storage->createBucket($bucketName);
//        $source = storage_path().'/app/google/glossary.csv';
//        $file = fopen($source, 'r');
//        $objectName = 'translation_v3_glossary_2020324';
//        $bucket = $storage->bucket($bucketName);
//        $object = $bucket->upload($file, [
//            'name' => $objectName
//        ]);
//         printf('Bucket created: %s' . PHP_EOL, $bucket->name());
////        $storage = new StorageClient();
////        foreach ($storage->buckets() as $bucket)
////        {
////            $info = $bucket->info();
////            printf("Bucket Metadata: %s" . PHP_EOL, print_r($info));
////        }

         //创建
//        $formattedParent = $translationServiceClient->locationName(
//            $projectId,
//            $location
//        );
//        $formattedName = $translationServiceClient->glossaryName(
//            $projectId,
//            $location,
//            $glossaryId
//        );
//        $languageCodesElement = 'en';
//        $languageCodesElement2 = 'ja';
//        $languageCodes = [$languageCodesElement, $languageCodesElement2];
//        $languageCodesSet = new LanguageCodesSet();
//        $languageCodesSet->setLanguageCodes($languageCodes);
//        $gcsSource = (new GcsSource())
//            ->setInputUri($inputUri);
//        $inputConfig = (new GlossaryInputConfig())
//            ->setGcsSource($gcsSource);
//        $glossary = (new Glossary())
//            ->setName($formattedName)
//            ->setLanguageCodesSet($languageCodesSet)
//            ->setInputConfig($inputConfig);
//
//        try {
//            $operationResponse = $translationServiceClient->createGlossary(
//                $formattedParent,
//                $glossary
//            );
//            $operationResponse->pollUntilComplete();
//            if ($operationResponse->operationSucceeded()) {
//                $response = $operationResponse->getResult();
//                printf('Created Glossary.' . PHP_EOL);
//                printf('Glossary name: %s' . PHP_EOL, $response->getName());
//                printf('Entry count: %s' . PHP_EOL, $response->getEntryCount());
//                printf(
//                    'Input URI: %s' . PHP_EOL,
//                    $response->getInputConfig()
//                        ->getGcsSource()
//                        ->getInputUri()
//                );
//            } else {
//                $error = $operationResponse->getError();
//                // handleError($error)
//            }
//        } finally {
//            $translationServiceClient->close();
//        }
        $text = "你好<br />欢迎来到<b>王者荣耀</b>";
        $targetLanguage = 'vi';
        $contents = [$text , '中国'];

        $mimeType = 'text/html';//text/plain
        $formattedParent = $translationServiceClient->locationName($projectId, 'global');
//        $response = $translationServiceClient->detectLanguage(
//            $formattedParent,
//            [
//                'content' => $text,
//                'mimeType' => $mimeType
//            ]
//        );
//        // Display list of detected languages sorted by detection confidence.
//        // The most probable language is first.
//        foreach ($response->getLanguages() as $language) {
//            // The language detected
//            printf('Language code: %s' . PHP_EOL, $language->getLanguageCode());
//            // Confidence of detection result for this language
//            printf('Confidence: %s' . PHP_EOL, $language->getConfidence());
//        }
//        die;

        try {
            $response = $translationServiceClient->translateText(
                $contents,
                $targetLanguage,
                $formattedParent,
                [
                    'sourceLanguage'=>'zh-CN',
                    'mimeType' => $mimeType
                ]
            );
            // Display the translation for each input text provided
            var_dump((array)($response->getTranslations()));
            foreach ($response->getTranslations() as $translation) {
                printf('Translated text: %s' . PHP_EOL, $translation->getTranslatedText());
            }
        } finally {
            $translationServiceClient->close();
        }

    }
}
