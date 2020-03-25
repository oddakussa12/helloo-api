<?php

namespace App\Services;

use GuzzleHttp\Pool;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\RequestException;
use Google\Cloud\Translate\TranslateClient;
use Google\Cloud\Translate\V3\TranslationServiceClient;

class V3TranslateService
{
    private $database;
    /**
     * @var TranslateClient
     */
    private $text;

    public $translate;

    protected $languages;

    private $formattedParent;

    protected $translations=array();

    public function __construct()
    {
        putenv('GOOGLE_APPLICATION_CREDENTIALS='.config('common.google_application_credentials'));
        $this->translate = new TranslationServiceClient();
        $this->formattedParent = $this->translate->locationName(config('common.google_project_id'), config('common.google_location'));
    }

    public function languages()
    {
        return $this->translate->languages();
    }

    public function detectLanguage($str , $option=array())
    {
        $options = array('format'=>config('translatable.translate_V3_default_format'));
        $options = $option+$options;
        $detectLanguages = $this->translate->detectLanguage($this->formattedParent , [
            'content' => $str,
            'mimeType' => $options['format']
        ])->getLanguages();
        $languages = array();
        foreach ($detectLanguages as $language)
        {
            array_push($languages , array('confidence'=>$language->getConfidence() , 'code'=>$language->getLanguageCode()));
        }
        $confidences = array_column($languages,'confidence');
        array_multisort($confidences,SORT_DESC,$languages);
        $strLanguage = array_shift($languages);
        return $strLanguage['code'];
    }


    public function translate($str , $option=array())
    {
        $options = array('target'=>config('translatable.translate_default_lang') , 'format'=>config('translatable.translate_V3_default_format'));
        $options = $option+$options;
        if(isset($options['resource']))
        {
            $resource = strval($options['resource']);
        }else{
            $resource = $this->detectLanguage($str);
        }
        if(isset($options['target'])&&$resource==$options['target'])
        {
            return $str;
        }
        try {
            $response = $this->translate->translateText(
                [$str],
                $options['target'],
                $this->formattedParent,
                [
                    'sourceLanguage'=>$resource,
                    'mimeType' => $options['format']
                ]
            );
            $translates = array();
            $translations = $response->getTranslations();
            foreach ($translations as $translation) {
                array_push($translates , $translation->getTranslatedText());
            }
            $translate = array_shift($translates);
        } finally {
            $this->translate->close();
        }
        return $translate;
    }

    public function pyChatTranslate($str , $option=array())
    {
        $options = array('target'=>config('translatable.translate_default_lang') , 'format'=>config('translatable.translate_default_format'));
        $options = $option+$options;
        $translate = $this->translate->translate($str , $options);
        return $translate['text'];
    }

    public function onlyTranslate(array $str , $option=array())
    {
        $options = array('target'=>config('translatable.translate_default_lang') , 'format'=>config('translatable.translate_default_format'));
        $options = $option+$options;
        return $this->translate->translateBatch($str , $options);
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
            'concurrency' => 6 ,
            'fulfilled'   => function (Response $response, $index){
                $lang = $this->languages[$index];
                Log::error("文本《{$this->text}》翻译{$lang}完成");
                $res = $response->getBody()->getContents();
                $res = json_decode($res, true);
                $this->translations[$lang] = array('comment_content'=>$res['data']['translations'][0]['translatedText']);
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
