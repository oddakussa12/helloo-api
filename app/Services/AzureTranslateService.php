<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Handler\CurlHandler;
use Psr\Http\Message\RequestInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;

class AzureTranslateService
{

    const MAX_RETRIES = 1;

    private $host = 'https://api.cognitive.microsofttranslator.com';

    private $path = '/detect?api-version=3.0';

    private $api_key = "";

    private $client;

    private $connect_timeout = 10;

    private $debug = false;

    private $decode_content = true;

    private $timeout = 20;

    /**
     * TranslateService constructor.
     */
    public function __construct()
    {
        $this->api_key = config('translatable.azure_translate_key');
        $this->client = new Client([
            // Base URI is used with relative requests
            'base_uri' => $this->host,
            // You can set any number of default request options.
            'connect_timeout'=>$this->connect_timeout,
            'debug'=>$this->debug,
            'decode_content'=>$this->decode_content,
            'verify'=>false,
            'timeout'=>$this->timeout,
//            'json' => $this->params,
            'headers' => [
                'Content-type' => 'application/json',
                'Ocp-Apim-Subscription-Key' => $this->api_key,
                'X-ClientTraceId' => $this->com_create_guid(),
            ]
        ]);
    }


    /**
     * @return array
     */
    public function languages()
    {
        return $this->translate->languages();
    }

    /**
     * @param $str
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function detectLanguage($str)
    {
        $texts = (array)$str;
        $languages = $this->detectLanguageBatch($texts);
        return $languages[0]['language'];
    }

    /**
     * @param array $str
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function detectLanguageBatch(array $str)
    {
        $texts = (array)$str;
        $json = array_map(function($v){
            return array('Text'=>strval($v));
        } , $texts);
        $response = $this->client->request('POST', $this->path  , array(
            'json'=>$json
        ));
        return \json_decode($response->getBody()->getContents() , true);
    }

    /**
     * @param array $str
     * @param array $option
     * @return array
     */
    public function translateBatch(array $str , $option=array())
    {

    }

    /**
     * @param $str
     * @param array $option
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function translate()
    {

    }

    /**
     * @param $str
     * @param array $option
     * @return mixed
     */
    public function pyChatTranslate($str , $option=array())
    {

    }

    /**
     * @param array $str
     * @param array $option
     * @return array
     */
    public function onlyTranslate(array $str , $option=array())
    {

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

    private function com_create_guid() {
        return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
            mt_rand( 0, 0xffff ),
            mt_rand( 0, 0x0fff ) | 0x4000,
            mt_rand( 0, 0x3fff ) | 0x8000,
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
        );
    }

    private function add_header($header, $value)
    {
        return function (callable $handler) use ($header, $value) {
            return function (
                RequestInterface $request,
                array $options
            ) use ($handler, $header, $value) {
                $request = $request->withHeader($header, $value);
                return $handler($request, $options);
            };
        };
    }
}
