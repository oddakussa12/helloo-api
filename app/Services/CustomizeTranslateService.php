<?php

namespace App\Services;

use GuzzleHttp\Pool;
use GuzzleHttp\Client;
use GuzzleHttp\Middleware;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use Google\Cloud\Translate\V2\TranslateClient;

class CustomizeTranslateService
{

    private $concurrency  = 9;

    /**
     * @var TranslateClient
     */
    public $translate;

    /**
     * @var array
     */
    protected $translations=array();
    /**
     * @var Client
     */
    private $client;
    /**
     * @var array
     */
    private $languages;
    /**
     * @var \Illuminate\Config\Repository|\Illuminate\Foundation\Application|mixed
     */
    private $key;

    private $connect_timeout = 5;

    private $debug = false;

    private $decode_content = true;

    private $timeout = 10;


    /**
     * TranslateService constructor.
     * @param array $languages
     */
    public function __construct($languages=array())
    {
        $this->languages = $languages;
        // 创建 Handler
        $handlerStack = HandlerStack::create(new CurlHandler());
        // 创建重试中间件，指定决策者为 $this->retryDecider(),指定重试延迟为 $this->retryDelay()
        $handlerStack->push(Middleware::retry($this->retryDecider(), $this->retryDelay()));
        $this->client = new Client([
            'base_uri' => 'https://translation.googleapis.com',
            'connect_timeout'=>$this->connect_timeout,
            'debug'=>$this->debug,
            'decode_content'=>$this->decode_content,
            'verify'=>false,
            'timeout'=>$this->timeout,
//            'form_params' => $this->params,
            'handler' => $handlerStack
        ]);
        $this->key = config('translatable.google_translate_key');
    }

    public function setLanguages(array $languages=array())
    {
        $this->languages = $languages;
        return $this;
    }
    
    /**
     * @param $str
     * @param array $option
     * @return mixed
     */
    public function translate($str , $option=array())
    {
        $source  = isset($option['source'])?$option['source']:null;
        $languages = $this->languages;
        $client = $this->client;
        $totalPageCount = count($languages);
        $param = $this->getParams($str , $this->key , $source);
        $requests = function ($total) use ($client , $languages , $param) {
            foreach ($languages as $key => $language) {
                $param['target'] = $language;
                $params['form_params'] = $param;
                yield function() use ($client , $params) {
                    return $client->postAsync('/language/translate/v2' , $params);
                };
            }
        };
        $pool = new Pool($client, $requests($totalPageCount), [
            'concurrency' => $this->concurrency,
            'fulfilled'   => function ($response, $index) use ($languages){
                $response = \json_decode($response->getBody()->getContents() , true);
//                $res = \json_encode($response , JSON_UNESCAPED_UNICODE);
                $language = $languages[$index];
                $data = $response['data'];
                $translations = array_shift($data['translations']);
                $translatedText = $translations['translatedText'];
                $this->translations[$language] = $translatedText;
//                \Log::error("请求第 $index 个请求，语言为：$language , 翻译为: ".$res);
            },
            'rejected' => function ($reason, $index) use ($languages){
                $language = $languages[$index];
                \Log::error("请求第 $index 个请求,语言 $language , 发生错误 ".$reason);
            },
        ]);
        // 开始发送请求
        $pool->promise()->wait();
        return $this;
    }

    public function getTranslations()
    {
        return $this->translations;
    }



    public function getParams($q , $key , $source=null , $format='html' , $model=null)
    {
        $param = [
            'q' => $q,
            'key' => $key,
        ];
        !empty($format)&&$param['format'] = $format;
        !empty($source)&&$param['source'] = $source;
        !empty($model)&&$param['model'] = $model;
        return $param;
    }

    protected function retryDecider()
    {
        return function ($retries,Request $request,Response $response = null,RequestException $exception = null) {
            // 超过最大重试次数，不再重试
            if ($retries >= self::MAX_RETRIES) {
                return false;
            }

            // 请求失败，继续重试
            if ($exception instanceof ConnectException) {
                return true;
            }

            if ($response) {
                // 如果请求有响应，但是状态码大于等于500，继续重试(这里根据自己的业务而定)
                if ($response->getStatusCode() >= 500) {
                    return true;
                }
            }

            return false;
        };
    }

    protected function retryDelay()
    {
        return function ($numberOfRetries) {
            return 1000 * $numberOfRetries;
        };
    }


}
