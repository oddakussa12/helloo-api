<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\ClientException;
use Google\Cloud\Translate\TranslateClient;

class TranslateService
{
    private $database;
    /**
     * @var TranslateClient
     */
    private $translate;

    protected $language;

    protected $translations=array();

    public function __construct()
    {
        $this->translate = new TranslateClient(array('key'=>config('translatable.google_translate_key')));
    }

    public function languages()
    {

        return $this->translate->languages();
    }

    public function detectLanguage($str)
    {
        $strLanguage = $this->translate->detectLanguage($str);
        return $strLanguage['languageCode'];
    }

    public function translate($str , $option=array())
    {
        $options = array('target'=>config('translatable.translate_default_lang') , 'format'=>config('translatable.translate_default_format'));
        $options = $option+$options;
        $lang = $this->detectLanguage($str);
        if(isset($options['target'])&&$lang==$options['target'])
        {
            return $str;
        }
        $translate = $this->translate->translate($str , $options);
        return $translate['text'];
    }

    public function customizeTrans($str , $contentLang)
    {
        $translations = array();
        $lang = $contentLang;
        $languages = config('translatable.locales');
        $index = array_search($lang , $languages);
        if($index!==false)
        {
            unset($languages[$index]);
        }
        if($contentLang=='und')
        {
            foreach ($languages as $v)
            {
                $translations[$v] = array('comment_content'=>$str);
            }
        }else{
            foreach ($languages as $v)
            {
                $translations[$v] = array('comment_content'=>$str.$v);
            }
//            $translations = $this->handle($str , $languages);
//            $translations[$lang] = array('comment_content'=>$str);
        }
        return $translations;
    }


    private function handle($text , $languages)
    {
        sort($languages);
        $this->language = $languages;
        $client = new Client();
        $apiKey = array(
            'AIzaSyCTYJF0QNu3rnLSFMHVwWfBT3lhM283TDU',
            'AIzaSyDKvJLifK80YtNUmMcZwchhTKjF8RQTORw',
            'AIzaSyAGbnluKfnUf61fV5zL1FE3p8u8XDQiaUE',
        );

        $requests = function ($language) use ($client , $text , $apiKey) {
            foreach ($language as $locale) {
                $uri = 'https://www.googleapis.com/language/translate/v2?key=' . $apiKey[array_rand($apiKey)] . '&q=' . rawurlencode($text) . '&target=' . $locale;
                yield function() use ($client, $uri) {
                    return $client->getAsync($uri);
                };
            }
        };

        $pool = new Pool($client, $requests($this->language), [
            'concurrency' => count($this->language),
            'fulfilled'   => function ($response, $index){
                $res = $response->getBody()->getContents();
                $res = json_decode($res, true);
                $this->translations[$this->language[$index]] = array('comment_content'=>$res['data']['translations'][0]['translatedText']);
                $this->countedAndCheckEnded();
            },
            'rejected' => function ($reason, $index){
//                echo "请求第 $index 个请求，用户 ".PHP_EOL;
//                $this->error("rejected" );
//                $this->error("rejected reason: " . $reason );
                $this->countedAndCheckEnded();
            },
        ]);

        $promise = $pool->promise();
        $promise->wait();
	if(array_key_exists('zh-TW' , $this->translations))
        {
            $this->translations['zh-HK'] = $this->translations['zh-TW'];
        }
        return $this->translations;

    }


    public function countedAndCheckEnded()
    {
//        if ($this->counter < $this->totalPageCount){
//            $this->counter++;
//            return;
//        }
//        $this->info("请求结束！");
    }

    public function info($message)
    {
        file_put_contents('log.log' , $message.PHP_EOL , FILE_APPEND);
    }

    public function error($message)
    {
        file_put_contents('logerror.log' , $message.PHP_EOL , FILE_APPEND);
    }







}
