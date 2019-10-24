<?php

namespace App\Services;

use GuzzleHttp\Pool;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\RequestException;
use Google\Cloud\Translate\TranslateClient;

class TranslateService
{
    private $database;
    /**
     * @var TranslateClient
     */
    private $text;

    private $translate;

    protected $languages;

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
        $languages = config('translatable.locales');
        if($contentLang=='und')
        {
            foreach ($languages as $v)
            {
                $translations[$v] = array('comment_content'=>$str);
            }
        }else{
            $index = array_search($contentLang , $languages);
            if($index!==false)
            {
                unset($languages[$index]);
            }
            unset($languages[array_search('zh-HK' , $languages)]);
            $translations = $this->handle($str , $languages);
            $translations[$contentLang] = array('comment_content'=>$str);
        }
        return $translations;
    }


    private function handle($text , $languages)
    {
        sort($languages);
        $this->languages = $languages;
        $this->text = $text;
        $client = new Client();
        $apiKey = array(
            'AIzaSyCTYJF0QNu3rnLSFMHVwWfBT3lhM283TDU',
            'AIzaSyDKvJLifK80YtNUmMcZwchhTKjF8RQTORw',
            'AIzaSyAGbnluKfnUf61fV5zL1FE3p8u8XDQiaUE',
        );
        Log::error("文本《{$text}》翻译开始");
        $requests = function ($language) use ($client , $text , $apiKey) {
            foreach ($language as $locale) {
                $data = array(
                    'q'=>$text,
                    'target'=>$locale,
                    'key'=>$apiKey[array_rand($apiKey)],
                );
                $url = 'https://translation.googleapis.com/language/translate/v2';
                $uri = 'https://www.googleapis.com/language/translate/v2?key=' . $apiKey[array_rand($apiKey)] . '&q=' . rawurlencode($text) . '&target=' . $locale;
                try {
                    yield function() use ($client, $url , $data) {
                        return $client->requestAsync('POST' , $url , ['form_params'=>$data]);
                    };
                }catch (\Exception $e)
                {
                    Log::error('获取异常:'.$e->getMessage());
                }
            }
        };

        $pool = new Pool($client, $requests($this->languages), [
            'concurrency' => count($this->languages),
            'fulfilled'   => function (Response $response, $index){
                $res = $response->getBody()->getContents();
                $res = json_decode($res, true);
                $this->translations[$this->languages[$index]] = array('comment_content'=>$res['data']['translations'][0]['translatedText']);
            },
            'rejected' => function (\Exception $reason, $index){
                $this->countedAndCheckEnded($reason , $index);
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


    public function countedAndCheckEnded($reason ,$index , $g='')
    {
        $text = $this->text;
        $reason = \json_encode($reason);
        $g = \json_encode($g);
        $lang = $this->languages[$index];
        Log::error("文本《{$text}》翻译{$lang}出错，原因:"."---------{$reason}--------{$g}");
    }







}
