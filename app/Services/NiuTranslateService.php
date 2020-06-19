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

class NiuTranslateService
{

    const MAX_RETRIES = 2;

    private $host = 'http://api.niutrans.com';

    private $path = '/NiuTransServer/translation';

    private $api_key = '';

    private $client;

    private $connect_timeout = 10;

    private $debug = false;

    private $decode_content = true;

    private $timeout = 20;

    private $params = array();

    /**
     * TranslateService constructor.
     */
    public function __construct()
    {
        $this->api_key = config('translatable.niu_translate_key');
        $this->params = array(
            'apikey'=>$this->api_key
        );
        // 创建 Handler
        $handlerStack = HandlerStack::create(new CurlHandler());
//        $handlerStack->push(Middleware::mapRequest(function (RequestInterface $request) {
//            \Log::error($request->getBody()->getContents());
//            return new Request(
//                $request->getMethod(),
//                $request->getUri(),
//                $request->getHeaders(),
//                $request->getBody()
//            );
//        }) , 'addHeader');

//        $handlerStack->after('addHeader', Middleware::mapRequest(function (RequestInterface $request) {
//            return new Request(
//                $request->getMethod(),
//                $request->getUri(),
//                $request->getHeaders(),
//                $request->getBody()
//            );
//        }));
        // 创建重试中间件，指定决策者为 $this->retryDecider(),指定重试延迟为 $this->retryDelay()
        $handlerStack->push(Middleware::retry($this->retryDecider(), $this->retryDelay()));

        $this->client = new Client([
            // Base URI is used with relative requests
            'base_uri' => $this->host,
            // You can set any number of default request options.
            'connect_timeout'=>$this->connect_timeout,
            'debug'=>$this->debug,
            'decode_content'=>$this->decode_content,
            'verify'=>false,
            'timeout'=>$this->timeout,
//            'form_params' => $this->params,
            'handler' => $handlerStack
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
     */
    public function detectLanguage($str)
    {

    }

    /**
     * @param array $str
     * @return array
     */
    public function detectLanguageBatch(array $str)
    {

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
    public function translate(string $str , $option=array())
    {
        if(!isset($option['to']))
        {
            $option['to']='en';
        }
        if(!isset($option['from']))
        {
            $option['from'] = app(AzureTranslateService::class)->detectLanguage($str);
        }
        $verification= fromAzureToNiu($option['from']);
        if(!$verification['support'])
        {
            return array('source'=>$option['from'] , 'translate'=>$str , 'target'=>$option['to']);
        }
        $option['from'] = $verification['language'];
        $option['to'] = SupportToNiu($option['to']);
        $option['src_text'] = $str;
        $params = $option+$this->params;
        $params = array_filter($params , function ($v , $k){
            return !blank($k)&&!blank($v);
        } , ARRAY_FILTER_USE_BOTH );
        $response = $this->client->request('POST', $this->path, [
            'form_params' => $params
        ]);
        $result = \json_decode($response->getBody()->getContents() , true);
        if(isset($result['error_code']))
        {
            \Log::error('Niu translation error：'.\json_encode($result));
            return array('source'=>$option['from'] , 'translate'=>$str , 'target'=>$option['to']);
        }
        return array('source'=>niuAzureToGoogle($result['from']) , 'translate'=>$result['tgt_text'] , 'target'=>niuAzureToGoogle($result['to']));
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
}
