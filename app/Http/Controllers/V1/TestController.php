<?php

namespace App\Http\Controllers\V1;

use App\Custom\RedisList;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;
use Google\Cloud\Storage\StorageClient;
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

        $storage = new StorageClient();
        foreach ($storage->buckets() as $bucket) {
            printf('Bucket: %s' . PHP_EOL, $bucket->name());
        }
//        $translationClient = new TranslationServiceClient();
//        $content = ['one', 'two', 'three'];
//        $targetLanguage = 'zh-CN';
//        $response = $translationClient->translateText(
//            $content,
//            $targetLanguage,
//            TranslationServiceClient::locationName('speachregins', 'global')
//        );
//
//        foreach ($response->getTranslations() as $key => $translation) {
//            $separator = $key === 2
//                ? '!'
//                : ', ';
//            echo $translation->getTranslatedText() . $separator;
//        }
//


        $translationServiceClient = new TranslationServiceClient();

        $projectId = 'speachregins';
        $targetLanguage = 'zh-CN';

        /** Uncomment and populate these variables in your code */
        $text = 'Hello, world!';
        // $targetLanguage = 'fr';
        // $projectId = '[Google Cloud Project ID]';
        $contents = [$text , 'hi'];
        $formattedParent = $translationServiceClient->locationName($projectId, 'global');

        try {
            $response = $translationServiceClient->translateText(
                $contents,
                $targetLanguage,
                $formattedParent
            );
            // Display the translation for each input text provided
            foreach ($response->getTranslations() as $translation) {
                printf('Translated text: %s' . PHP_EOL, $translation->getTranslatedText());
            }
        } finally {
            $translationServiceClient->close();
        }

    }
}
